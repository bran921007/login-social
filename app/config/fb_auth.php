<?php
return array(
    "base_url" => "http://b2.com/fbauth/auth",
    "providers" => array (
        "Facebook" => array (
            "enabled" => TRUE,
            "keys" => array ("id" => "APP_ID", "secret" =>"APP_SECRET"),
            "scope" => "public_profile,email"
        )
    )
);