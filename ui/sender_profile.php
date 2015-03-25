<?php

require_once __DIR__ . "/login-check.php";

use Access2Me\Filter;
use Access2Me\Helper;
use Access2Me\Model;

function formatFiltersStat($filtersStat)
{
    $stat = [];

    foreach ($filtersStat['filters'] as $record) {
        $type = $record['type'];
        $property = $type->properties[$record['filter']->getProperty()];

        $comparator = $record['comparator'];
        $method = $comparator->methods[$record['filter']->getMethod()];

        // format condition
        $condition = $type->name
            . ' ' . $property['name']
            . ' ' . $method['description']
            . ' "' . htmlentities($record['filter']->getValue()) . '"';

        // format value
        $value = $property['name'] . ' is ';
        $count = count($record['value']);
        if ($count == 0) {
            $value .= '**EMPTY**';
        } else {
            if ($count > 1) {
                $value .= 'one of ';
            }

            $value .= implode(
                ', ',
                array_map(
                    function($v) { return '"' . htmlentities($v) . '"'; },
                    $record['value']
                )
            );
        }

        $descr = [
            'condition' => $condition,
            'value' => $value,
            'status' => $record['status']
        ];

        $stat[] = $descr;
    }

    return $stat;
}

// controller like function :)
function getSendersProfile()
{
    $data = [];
    $email = isset($_GET['email']) ? $_GET['email'] : null;
    
    $db = new Database;

    // check if current user has messages from the sender
    $user = Helper\Registry::getAuth()->getLoggedUser();
    $mesgRepo = new Model\MessageRepository($db);
    $messages = $mesgRepo->findByUserAndSender($user['id'], $email);
   
    if (empty($messages)) {
        $data['error'] = 'No such sender';
        return $data;
    }

    // get all services for the sender
    $senderRepo = new Model\SenderRepository($db);
    $senders = $senderRepo->getByEmail($email);

    if (empty($senders)) {
        $data['error'] = 'Sender didn\'t verify himself yet';
        return $data;
    }

    // get all profiles of the sender
    $defaultProfileProvider = Helper\Registry::getProfileProvider();
    try {
        $data['profiles'] = [
            'sender' => $senders[0]->getSender(),
            'profile' => new Helper\ProfileCombiner($defaultProfileProvider->getProfiles($senders))
        ];

        // get and apply filters to the senders profile
        $filterRepo = new Model\FiltersRepository($db);
        $filters = $filterRepo->findByUserId($user['id']);

        $filterProcessor = new Filter\Processor($filters);
        $filterProcessor->process($data['profiles']['profile']);
        
        $data['filterStat'] = formatFiltersStat($filterProcessor->getStat());
       
    } catch (Exception $ex) {
        $errMsg = 'Can\'t retrieve profile of ' . $email;
        Logging::getLogger()->info($errMsg);
        $data['error'] = 'Unfortunately we can\'t retrieve sender\'s profile right now.';
        return $data;
    }

    return $data;
}

try {
    $data = getSendersProfile();
} catch (\Exception $ex) {
    Logging::getLogger()->error($ex->getMessage(), array('exception' => $ex));
    Helper\Http::generate500();
}
?>
<?php include 'inc/config.php'; $template['header_link'] = 'THE END OF SPAM AS WE KNOW IT'; ?>
<?php include 'inc/template_start.php'; ?>
<?php include 'inc/page_head.php'; ?>
<?php echo Helper\Registry::getTwig()->render('sender_profile.html.twig', $data); ?>
<?php include 'inc/page_footer.php'; ?>
<?php include 'inc/template_scripts.php'; ?>
<?php include 'inc/template_end.php'; ?>