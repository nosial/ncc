#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <limits.h>

int main(int argc, char *argv[])
{
    // Get the program's file path
    char programPath[PATH_MAX];
    ssize_t pathLength = readlink("/proc/self/exe", programPath, sizeof(programPath) - 1);
    if (pathLength == -1)
    {
        perror("readlink");
        return 1;
    }

    programPath[pathLength] = '\0';

    // Calculate the total length needed for the command string
    size_t totalLength = snprintf(NULL, 0, "ncc exec --package=\"%s\" --exec-args", programPath);
    for (int i = 1; i < argc; i++)
    {
        totalLength += snprintf(NULL, 0, " \"%s\"", argv[i]) + 1; // +1 for space or null terminator
    }

    // Allocate memory for the command string
    char *command = (char *)malloc(totalLength + 1); // +1 for null terminator
    if (command == NULL)
    {
        perror("malloc");
        return 1;
    }

    // Construct the command to execute
    snprintf(command, totalLength + 1, "ncc exec --package=\"%s\" --exec-args", programPath);
    for (int i = 1; i < argc; i++)
    {
        snprintf(command + strlen(command), totalLength - strlen(command) + 1, " \"%s\"", argv[i]);
    }

    // Execute the ncc command
    int result = system(command);
    free(command);

    if (result == -1)
    {
        perror("system");
        return 1;
    }

    return WEXITSTATUS(result);
}
