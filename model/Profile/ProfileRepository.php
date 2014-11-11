<?php

namespace Access2Me\Model\Profile;

class ProfileRepository
{
    public static function getDisplayName(\ReflectionProperty $prop)
    {
        $comment = $prop->getDocComment();
        
        if ($comment !== false
            && preg_match('/@displayName\s+(.+)$/m', $comment, $matches) != false
        ) {
            return $matches[1];
        }

        return null;
    }
}
