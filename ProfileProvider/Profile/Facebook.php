<?php

namespace Access2Me\ProfileProvider\Profile;

class Facebook
{
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

    public $pictureUrl;

    public $profileUrl;

    /**
     * @displayName Location
     */
    public $location;

    public $positions = array();

    /**
     * @displayName Website
     */
    public $website;
}
