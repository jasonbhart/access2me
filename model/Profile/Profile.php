<?php

namespace Access2Me\Model\Profile;

class Profile
{
     /**
     * @displayName First name
     */
    public $firstName;

    /**
     * @displayName Last name
     */
    public $lastName;

    /**
     * @displayName Full name
     */
    public $fullName;

    /**
     * @displayName Birthday
     */
    public $birthday;

    /**
     * @displayName Biography
     */
    public $biography;

    /**
     * @displayName Gender
     */
    public $gender;

    /**
     * @displayName Email
     */
    public $email;

    /**
     * @displayName Headline
     */
    public $headline;

    public $pictureUrl;

    public $profileUrl;

    /**
     * @displayName Location
     */
    public $location;

    /**
     * @displayName Industry
     */
    public $industry;

    /**
     * @displayName Summary
     */
    public $summary;

    /**
     * @displayName Specialties
     */
    public $specialties;

    public $associations;

    /**
     * @displayName Interests
     */
    public $interests;

    public $positions = array();

    /**
     * @displayName Website
     */
    public $website;

    /**
     * @displayName Connections
     */
    public $connections;
}
