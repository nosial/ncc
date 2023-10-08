#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <limits.h>

// Function to check if a file exists in the given path
int fileExists(const char *path) {
    return access(path, F_OK) != -1;
}

// Function to find 'ncc' in the $PATH
char *findNCCInPath() {
    char *path = getenv("PATH");
    if (path == NULL) {
        return NULL;
    }

    char *token = strtok(path, ":");
    while (token != NULL) {
        char fullPath[PATH_MAX];
        snprintf(fullPath, sizeof(fullPath), "%s/%s", token, "ncc");
        if (fileExists(fullPath)) {
            return strdup(fullPath);
        }
        token = strtok(NULL, ":");
    }
    return NULL;
}

int main(int argc, char *argv[]) {
    // Find 'ncc' in the $PATH
    char *nccPath = findNCCInPath();
    if (nccPath == NULL) {
        fprintf(stderr, "Error: 'ncc' needs to be installed on the system or added to the $PATH to execute this program.\n");
        return 1;
    }

    // Get the program's file path
    char programPath[PATH_MAX];
    ssize_t pathLength = readlink("/proc/self/exe", programPath, sizeof(programPath) - 1);
    if (pathLength == -1) {
        perror("readlink");
        free(nccPath);
        return 1;
    }
    programPath[pathLength] = '\0';

    // Calculate the total length needed for the command string
    size_t totalLength = strlen(nccPath) + strlen(programPath) + 50; // 50 for additional characters
    for (int i = 1; i < argc; i++) {
        totalLength += strlen(argv[i]) + 3; // +3 for quotes and space
    }

    // Allocate memory for the command string
    char *command = (char *)malloc(totalLength);
    if (command == NULL) {
        perror("malloc");
        free(nccPath);
        return 1;
    }

    // Construct the command to execute
    snprintf(command, totalLength, "%s exec --package=\"%s\" --exec-args", nccPath, programPath);
    for (int i = 1; i < argc; i++) {
        snprintf(command + strlen(command), totalLength - strlen(command), " \"%s\"", argv[i]);
    }

    // Execute the ncc command
    int result = system(command);
    free(command);
    free(nccPath);

    if (result == -1) {
        perror("system");
        return 1;
    }

    return WEXITSTATUS(result);
}