<?php
use Access2Me\Model\SenderRepository;

function out($value, $default='Unavailable') {
    echo htmlspecialchars(!empty($value) ? $value : $default);
}
?>
<!doctype html>
<html>
<head>
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">

    <!-- Optional theme -->
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">

    <!-- Latest compiled and minified JavaScript -->
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
</head>
<body>

<div class="container">
    <div class="page-header">
        <h1>Profile page of <?php echo htmlspecialchars($profile['sender']); ?><span class="pull-right label label-default">Access2.me</span></h1>
    </div>

    <?php
        if (isset($profile['services'][SenderRepository::SERVICE_LINKEDIN])):
            $data = $profile['services'][SenderRepository::SERVICE_LINKEDIN]['profile'];
    ?>
    <div class="panel panel-default">
        <div class="panel-heading">Linkedin</div>
        <table class="table">
            <tr>
                <td>Name</td><td><?php out($data['name']); ?></td>
            </tr>
            <tr>
                <td>Email</td><td><?php out($data['email']); ?></td>
            </tr>
            <tr>
                <td>Website</td><td><?php out($data['website']); ?></td>
            </tr>
        </table>
    </div>
    <?php endif; ?>

    <?php
        if (isset($profile['services'][SenderRepository::SERVICE_FACEBOOK])):
            $data = $profile['services'][SenderRepository::SERVICE_FACEBOOK]['profile'];
    ?>
    <div class="panel panel-default">
        <div class="panel-heading">Facebook</div>
        <table class="table">
            <tr>
                <td>Picture</td><td><img src="<?php out($data['picture_url']); ?>" /></td>
            </tr>
            <tr>
                <td>Name</td><td><?php out($data['name']); ?></td>
            </tr>
            <tr>
                <td>Email</td><td><?php out($data['email']); ?></td>
            </tr>
            <tr>
                <td>Biography</td><td><?php out($data['biography']); ?></td>
            </tr>
            <tr>
                <td>Birthday</td><td><?php out($data['birthday']); ?></td>
            </tr>
            <tr>
                <td>Gender</td><td><?php out($data['gender']); ?></td>
            </tr>
            <tr>
                <td>Link to profile</td><td><?php out($data['link']); ?></td>
            </tr>
            <tr>
                <td>Email</td><td><?php out($data['location']); ?></td>
            </tr>
            <tr>
                <td>Website</td><td><?php out($data['website']); ?></td>
            </tr>
            <tr>
                
            </tr>
        </table>
    </div>
    <?php endif; ?>

    <?php
        if (isset($profile['services'][SenderRepository::SERVICE_TWITTER])):
            $data = $profile['services'][SenderRepository::SERVICE_TWITTER]['profile'];
    ?>
    <div class="panel panel-default">
        <div class="panel-heading">Twitter</div>
        <table class="table">
            <tr>
                <td>Name</td><td><?php out($data['name']); ?></td>
            </tr>
            <tr>
                <td>Email</td><td><?php out($data['email']); ?></td>
            </tr>
            <tr>
                <td>Biography</td><td><?php out($data['biography']); ?></td>
            </tr>
            <tr>
                <td>Birthday</td><td><?php out($data['birthday']); ?></td>
            </tr>
            <tr>
                <td>Gender</td><td><?php out($data['gender']); ?></td>
            </tr>
            <tr>
                <td>Link to profile</td><td><?php out($data['link']); ?></td>
            </tr>
            <tr>
                <td>Email</td><td><?php out($data['location']); ?></td>
            </tr>
            <tr>
                <td>Website</td><td><?php out($data['website']); ?></td>
            </tr>
            <tr></tr>
        </table>
    </div>
    <?php endif; ?>

</body>
</html>