<?php
return array(
    "base_url" => "http://104.236.203.200/twitterAuth/auth", //write your own url here
    "providers" => array (
        "Twitter" => array (
            "enabled" => true,
            "keys" => array ("key" => "CONSUMER_KEY",
                "secret" => "CONSUMER_SECRET")
        )
    )
);