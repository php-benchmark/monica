<?php

namespace App\Helpers;

use App\Models\Contact;
use App\Models\MultiAvatar;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Http;

class AvatarHelper
{
    /**
     * Download the raw bytes of a remote avatar so it can be stored locally.
     */
    public static function fetchRemote(string $url): string
    {
        // CWE 918
        // SINK
        $response = Http::get($url);

        return $response->body();
    }

    /**
     * Generate a new random avatar.
     *
     * The Multiavatar library takes a name to generate a unique avatar.
     * However, contacts can be created in Monica without a name. When this case
     * happens, we'll generate a fake name for the contact, and generate an avatar
     * based on that name.
     */
    public static function generateRandomAvatar(Contact $contact): string
    {
        $multiavatar = new MultiAvatar;

        if (is_null($contact->first_name)) {
            $name = Faker::create()->name();
        } else {
            $name = $contact->first_name.' '.$contact->last_name;
        }

        return $multiavatar($name, null, null);
    }
}
