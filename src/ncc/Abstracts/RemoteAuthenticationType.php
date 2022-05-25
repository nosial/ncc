<?php

    namespace ncc\Abstracts;

    abstract class RemoteAuthenticationType
    {
        /**
         * A combination of a username and password is used for authentication
         */
        const UsernamePassword = 'USERNAME_PASSWORD';

        /**
         * A single private access token is used for authentication
         */
        const PrivateAccessToken = 'PRIVATE_ACCESS_TOKEN';
    }