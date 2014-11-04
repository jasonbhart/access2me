<?php

namespace Access2Me\Model\Profile;

class ProfileRepository
{
    private static $descriptions = array(
        'firstName' => 'First name',
        'lastName' => 'Last name',
        'fullName' => 'Full name',
        'birthday' => 'Birthday',
        'biography' => 'Biography',
        'gender' => 'Gender',
        'email' => 'Email',
        'headline' => 'Headline',
        'location' => 'Location',
        'industry' => 'Industry',
        'summary' => 'Summary',
        'specialties' => 'Specialties',
        'interests' => 'Interests',
        'website' => 'Website',
        'connections' => 'Connections'
    );
    
    public static function getFilterableFields()
    {
        $refl = new \ReflectionClass('\Access2Me\Model\Profile\Profile');
        $fields = array();
        foreach ($refl->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $name = $prop->getName();
            if (isset(self::$descriptions[$name])) {
                $fields[$name] = self::$descriptions[$name];
            }
        }

        return $fields;
    }

}
