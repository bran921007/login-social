<?php
return array(
    "base_url" => "http://b2.com/twitterAuth/auth", //write your own url here
    "providers" => array (
        "Twitter" => array (
            "enabled" => true,
            "keys" => array ("key" => "CONSUMER_KEY",
                "secret" => "CONSUMER_SECRET")
        )
    )
);