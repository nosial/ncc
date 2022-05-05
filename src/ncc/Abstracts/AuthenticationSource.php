<?php

    namespace ncc\Abstracts;

    abstract class AuthenticationSource
    {
        const None = 'NONE';

        const ServerProvided = 'SERVER_PROVIDED';

        const UserProvided = 'USER_PROVIDED';
    }