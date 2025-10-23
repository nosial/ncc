#!/bin/sh
#
# Copyright (c) Nosial 2022-2025, all rights reserved.
#
#  Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
#  associated documentation files (the "Software"), to deal in the Software without restriction, including without
#  limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the
#  Software, and to permit persons to whom the Software is furnished to do so, subject to the following
#  conditions:
#
#  The above copyright notice and this permission notice shall be included in all copies or substantial portions
#  of the Software.
#
#  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
#  INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
#  PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
#  LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
#  OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
#  DEALINGS IN THE SOFTWARE.
#
#

# Nosial Code Compiler Installer
# This script will install the Nosial Code Compiler on your system.

# Color output support (fallback to no color if not supported)
if [ -t 1 ]; then
    RED=$(printf '\033[0;31m')
    GREEN=$(printf '\033[0;32m')
    YELLOW=$(printf '\033[1;33m')
    BLUE=$(printf '\033[0;34m')
    NC=$(printf '\033[0m')
else
    RED=''
    GREEN=''
    YELLOW=''
    BLUE=''
    NC=''
fi

# Print functions
print_info() {
    printf "%s[INFO]%s %s\n" "${BLUE}" "${NC}" "$1"
}

print_success() {
    printf "%s[SUCCESS]%s %s\n" "${GREEN}" "${NC}" "$1"
}

print_warning() {
    printf "%s[WARNING]%s %s\n" "${YELLOW}" "${NC}" "$1"
}

print_error() {
    printf "%s[ERROR]%s %s\n" "${RED}" "${NC}" "$1" >&2
}

# Check if running as root/sudo
check_root() {
    if [ "$(id -u)" -ne 0 ]; then
        print_warning "Not running as root. You may need sudo privileges for installation."
        USE_SUDO="sudo"
    else
        USE_SUDO=""
    fi
}

# Check if PHP is installed
check_php() {
    if ! command -v php >/dev/null 2>&1; then
        print_error "PHP is not installed or not in PATH"
        print_error "Please install PHP before installing ncc"
        exit 1
    fi
    
    PHP_VERSION=$(php -r "echo PHP_VERSION;" 2>/dev/null)
    if [ -z "$PHP_VERSION" ]; then
        print_error "Failed to detect PHP version"
        exit 1
    fi
    
    print_info "Found PHP version: $PHP_VERSION"
}

# Get PHP include path and determine installation directory
get_php_include_path() {
    PHP_INCLUDE_PATH=$(php -r "echo get_include_path();" 2>/dev/null)
    
    if [ -z "$PHP_INCLUDE_PATH" ]; then
        print_error "Failed to get PHP include path"
        exit 1
    fi
    
    print_info "PHP include path: $PHP_INCLUDE_PATH"
    
    # Parse the include path (separated by :)
    # Skip the first path if it's "." (current directory)
    INSTALL_DIR=""
    IFS=':'
    for path in $PHP_INCLUDE_PATH; do
        # Skip current directory
        if [ "$path" = "." ]; then
            continue
        fi
        
        # Check if path exists and is writable (or can be made writable with sudo)
        if [ -d "$path" ]; then
            if [ -w "$path" ] || [ -n "$USE_SUDO" ]; then
                INSTALL_DIR="$path"
                break
            fi
        fi
    done
    unset IFS
    
    if [ -z "$INSTALL_DIR" ]; then
        print_error "Could not determine suitable installation directory"
        print_error "No writable directory found in PHP include path"
        exit 1
    fi
    
    print_info "Selected installation directory: $INSTALL_DIR"
}

# Find suitable bin directory in PATH
get_bin_path() {
    BIN_DIR=""
    
    # Preferred bin directories in order
    PREFERRED_BINS="/usr/local/bin /usr/bin /bin"
    
    for dir in $PREFERRED_BINS; do
        if [ -d "$dir" ]; then
            if [ -w "$dir" ] || [ -n "$USE_SUDO" ]; then
                BIN_DIR="$dir"
                break
            fi
        fi
    done
    
    if [ -z "$BIN_DIR" ]; then
        print_error "Could not find suitable bin directory in PATH"
        exit 1
    fi
    
    print_info "Selected bin directory: $BIN_DIR"
}

# Find ncc.phar file
find_phar() {
    SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
    PHAR_FILE=""
    
    # Check in same directory as script
    if [ -f "$SCRIPT_DIR/ncc.phar" ]; then
        PHAR_FILE="$SCRIPT_DIR/ncc.phar"
    # Check in ../target directory
    elif [ -f "$SCRIPT_DIR/../target/ncc.phar" ]; then
        PHAR_FILE="$SCRIPT_DIR/../target/ncc.phar"
    # Check in parent directory
    elif [ -f "$SCRIPT_DIR/../ncc.phar" ]; then
        PHAR_FILE="$SCRIPT_DIR/../ncc.phar"
    else
        print_error "Could not find ncc.phar file"
        print_error "Searched in:"
        print_error "  - $SCRIPT_DIR/ncc.phar"
        print_error "  - $SCRIPT_DIR/../target/ncc.phar"
        print_error "  - $SCRIPT_DIR/../ncc.phar"
        exit 1
    fi
    
    print_info "Found ncc.phar: $PHAR_FILE"
}

# Install ncc
install_ncc() {
    print_info "Installing ncc..."
    
    # Create target directory if it doesn't exist
    TARGET_PHAR="$INSTALL_DIR/ncc"
    
    # Copy phar file
    if [ -n "$USE_SUDO" ]; then
        $USE_SUDO cp "$PHAR_FILE" "$TARGET_PHAR" || {
            print_error "Failed to copy ncc.phar to $TARGET_PHAR"
            exit 1
        }
        $USE_SUDO chmod 644 "$TARGET_PHAR" || {
            print_error "Failed to set permissions on $TARGET_PHAR"
            exit 1
        }
    else
        cp "$PHAR_FILE" "$TARGET_PHAR" || {
            print_error "Failed to copy ncc.phar to $TARGET_PHAR"
            exit 1
        }
        chmod 644 "$TARGET_PHAR" || {
            print_error "Failed to set permissions on $TARGET_PHAR"
            exit 1
        }
    fi
    
    print_success "Installed ncc to $TARGET_PHAR"
    
    # Create executable wrapper script in bin directory
    BIN_SCRIPT="$BIN_DIR/ncc"
    WRAPPER_CONTENT="#!/bin/sh
# Nosial Code Compiler wrapper script
exec php \"$TARGET_PHAR\" --ncc-cli \"\$@\"
"
    
    if [ -n "$USE_SUDO" ]; then
        printf "%s" "$WRAPPER_CONTENT" | $USE_SUDO tee "$BIN_SCRIPT" >/dev/null || {
            print_error "Failed to create wrapper script at $BIN_SCRIPT"
            exit 1
        }
        $USE_SUDO chmod 755 "$BIN_SCRIPT" || {
            print_error "Failed to set permissions on $BIN_SCRIPT"
            exit 1
        }
    else
        printf "%s" "$WRAPPER_CONTENT" > "$BIN_SCRIPT" || {
            print_error "Failed to create wrapper script at $BIN_SCRIPT"
            exit 1
        }
        chmod 755 "$BIN_SCRIPT" || {
            print_error "Failed to set permissions on $BIN_SCRIPT"
            exit 1
        }
    fi
    
    print_success "Created executable wrapper at $BIN_SCRIPT"
    print_success "Installation complete!"
    print_info "You can now run 'ncc' from anywhere in your terminal"
    print_info "PHP can also require ncc globally: require 'ncc';"
}

# Uninstall ncc
uninstall_ncc() {
    print_info "Uninstalling ncc..."
    
    REMOVED=0
    
    # Find and remove phar file from PHP include paths
    PHP_INCLUDE_PATH=$(php -r "echo get_include_path();" 2>/dev/null)
    if [ -n "$PHP_INCLUDE_PATH" ]; then
        IFS=':'
        for path in $PHP_INCLUDE_PATH; do
            if [ "$path" = "." ]; then
                continue
            fi
            
            if [ -f "$path/ncc" ]; then
                print_info "Removing $path/ncc"
                if [ -n "$USE_SUDO" ]; then
                    $USE_SUDO rm -f "$path/ncc" && REMOVED=1
                else
                    rm -f "$path/ncc" && REMOVED=1
                fi
            fi
        done
        unset IFS
    fi
    
    # Remove bin wrapper from common locations
    COMMON_BINS="/usr/local/bin/ncc /usr/bin/ncc /bin/ncc"
    for bin in $COMMON_BINS; do
        if [ -f "$bin" ]; then
            print_info "Removing $bin"
            if [ -n "$USE_SUDO" ]; then
                $USE_SUDO rm -f "$bin" && REMOVED=1
            else
                rm -f "$bin" && REMOVED=1
            fi
        fi
    done
    
    if [ "$REMOVED" -eq 1 ]; then
        print_success "Uninstallation complete!"
    else
        print_warning "No ncc installation found"
    fi
}

# Show usage
show_usage() {
    printf "Nosial Code Compiler Installer\n\n"
    printf "Usage: %s [COMMAND]\n\n" "$0"
    printf "Commands:\n"
    printf "  install    Install or update ncc (default)\n"
    printf "  uninstall  Remove ncc from the system\n"
    printf "  help       Show this help message\n\n"
}

# Main installation logic
main() {
    COMMAND="${1:-install}"
    
    case "$COMMAND" in
        install|update)
            print_info "Starting ncc installation..."
            check_root
            check_php
            get_php_include_path
            get_bin_path
            find_phar
            install_ncc
            ;;
        uninstall|remove)
            print_info "Starting ncc uninstallation..."
            check_root
            check_php
            uninstall_ncc
            ;;
        help|--help|-h)
            show_usage
            ;;
        *)
            print_error "Unknown command: $COMMAND"
            show_usage
            exit 1
            ;;
    esac
}

# Run main function
main "$@"