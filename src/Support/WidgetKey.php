<?php

namespace Datalumo\Laravel\Support;

final class WidgetKey
{
    public static function search(): string
    {
        return self::firstConfigured(['datalumo.search_widget', 'datalumo.widget']);
    }

    public static function chat(): string
    {
        return self::firstConfigured(['datalumo.chat_widget', 'datalumo.widget']);
    }

    /** Full embed key, or public id if the org prefix is omitted. */
    public static function publicId(string $key): string
    {
        if (str_contains($key, '/')) {
            return (string) substr($key, strrpos($key, '/') + 1);
        }

        return $key;
    }

    /** Prefer prejoined org/widget; otherwise build from organisation config. */
    public static function embedKey(string $key): string
    {
        if ($key === '' || str_contains($key, '/')) {
            return $key;
        }

        $org = (string) config('datalumo.organisation', '');

        return $org !== '' ? $org.'/'.$key : $key;
    }

    /**
     * @param  list<string>  $keys
     */
    private static function firstConfigured(array $keys): string
    {
        foreach ($keys as $key) {
            $value = (string) config($key, '');
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
