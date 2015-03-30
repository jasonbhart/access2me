<?php

namespace Access2Me\ProfileProvider\Profile;


class Google
{
    public $id;
    public $summary;
    public $firstName;
    public $lastName;
    public $birthday;
    public $pictureUrl;
    public $profileUrl;
    public $gender;
    public $ageRange = ['min' => null, 'max' => null];
    public $location;
    public $emails = [];
    public $occupation;
    public $relationshipStatus;
    public $organizations = [];
}
