<?php

namespace App\Auth;

use Hybridauth\Storage\StorageInterface;
use Illuminate\Contracts\Session\Session;

final class HybridSessionStorage implements StorageInterface
{
    public function __construct(private Session $session) {}

    public function get($key)
    {
        return $this->session->get('hybridauth', [])[strtolower($key)] ?? null;
    }

    public function set($key, $value)
    {
        $data = $this->session->get('hybridauth', []);
        $data[strtolower($key)] = $value;
        $this->session->put('hybridauth', $data);
    }

    public function delete($key)
    {
        $data = $this->session->get('hybridauth', []);
        unset($data[strtolower($key)]);
        $this->session->put('hybridauth', $data);
    }

    public function deleteMatch($key)
    {
        $data = $this->session->get('hybridauth', []);
        foreach ($data as $stored => $value) {
            if (str_contains($stored, strtolower($key))) {
                unset($data[$stored]);
            }
        }
        $this->session->put('hybridauth', $data);
    }

    public function clear()
    {
        $this->session->forget('hybridauth');
    }
}
