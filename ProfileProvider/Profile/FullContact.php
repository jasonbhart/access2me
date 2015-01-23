<?php

namespace Access2Me\ProfileProvider\Profile;

class FullContact
{
    public $likelihood;

    public $firstName;
    public $lastName;
    public $fullName;
    public $gender;

    /*
     * [0] => (string) http://fullcontact.com
     */
    public $websites = [];

    /*
     * [0] => array(2) (
     *   [client] => (string) gtalk
     *   [handle] => (string) lorangb@gmail.com
     * )
     */
    public $messengers = [];

    /*
     * [0] => array(4) (
     *   [typeId] => (string) userclaim
     *   [typeName] => (string) userClaim
     *   [url] => (string) https://d2ojpxxtu63wzl.cloudfront.net/static/c4ac87f9534294c601e7e19a5beb7e99_7d8dbffd5197a74ddc52c0e0f38ef2115f3a451a29b772acdcea6675712d51d7
     *   [isPrimary] => 1
     * )
     */
    public $photos = [];

    /*
     *  [0] => array(5) (
     *    [isPrimary] => (bool) true
     *    [name] => (string) FullContact
     *    [startDate] => (string) 2010-01
     *    [title] => (string) Co-Founder & CEO
     *    [current] => (bool) true
     *  )
     */
    public $organizations = [];

    /*
     * [0] => array(4) (
     *   [typeId] => (string) googleplus
     *   [typeName] => (string) Google Plus
     *   [url] => (string) https://plus.google.com/114426306375480734745
     *   [id] => (string) 114426306375480734745
     * )
     */
    public $socialProfiles = [];
}
