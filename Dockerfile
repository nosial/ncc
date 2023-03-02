#   Copyright 2022-2023 Nosial - All Rights Reserved.
#
#   Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
#   documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
#   rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
#   permit persons to whom the Software is furnished to do so, subject to the following conditions:
#
#   The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
#   Software.
#
#   THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
#   WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
#   COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
#   OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
#
#   ncc in Docker
#
#   This image is intended to be used as a base for projects using ncc.
#

#
# Builder stage: downloads necessary files and serves them on a silver platter.
#

FROM php:8.1 AS builder
WORKDIR /tmp

# Install some stuff the default image doesn't come with
RUN apt update -yqq;                                                            \
    apt install git libpq-dev libzip-dev zip make wget gnupg -yqq

# Download phive
RUN wget -O phive.phar https://phar.io/releases/phive.phar;                     \
    wget -O phive.phar.asc https://phar.io/releases/phive.phar.asc;             \
    gpg --keyserver hkps://keys.openpgp.org --recv-keys 0x9D8A98B29B2D5D79;     \
    gpg --verify phive.phar.asc phive.phar;                                     \
    rm phive.phar.asc

# Download the latest version of ncc (Nosial Code Compiler)
RUN git clone https://git.n64.cc/nosial/ncc.git
WORKDIR /tmp/ncc
RUN make redist

#
# Main stage: Copies downloaded files and installs all
#

FROM php:8.1-alpine
WORKDIR /tmp

# Copy downloaded files
COPY --from=builder /tmp/. .

# Install phive...
RUN chmod +x phive.phar;                                                        \
    mv phive.phar /usr/local/bin/phive;                                         \

    # ... and phab...
    phive install phpab --global --trust-gpg-keys 0x2A8299CE842DD38C;           \

    # ... and ncc.
    php /tmp/ncc/build/src/INSTALL --auto --install-composer;                   \
    rm -rf /tmp/*
