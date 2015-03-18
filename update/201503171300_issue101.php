<?php

require_once __DIR__ . '/../boot.php';

use Access2Me\Filter;
use Access2Me\Helper;
use Access2Me\Model;

$db = Helper\Registry::getDatabase();

// add column to store method id in new format (equal, not equal, lesser, etc.)
$query = 'ALTER TABLE `filters` ADD `method` TINYINT NULL AFTER `field`';
$db->execute($query);

// change `type` to tinyint type
$query = 'ALTER TABLE `filters` MODIFY `type` TINYINT NOT NULL';
$db->execute($query);

// rename `field` to `property`
$query = 'ALTER TABLE `filters` CHANGE `field` `property` VARCHAR (100) NOT NULL';
$db->execute($query);

$mapTextType = [
    \Filter::EQUAL_TO => Filter\Comparator\TextComparator::EQUALS,
    \Filter::NOT_EQUAL_TO => Filter\Comparator\TextComparator::NOT_EQUALS,
];

$mapNumericType = [
    \Filter::EQUAL_TO => Filter\Comparator\NumericComparator::EQUALS,
    \Filter::NOT_EQUAL_TO => Filter\Comparator\NumericComparator::NOT_EQUALS,
    \Filter::GREATER_THAN => Filter\Comparator\NumericComparator::GREATER,
    \Filter::NOT_GREATER_THAN => Filter\Comparator\NumericComparator::NOT_GREATER
];

$map = [
    'firstName' => [ Filter\TypeFactory::LINKEDIN, 'firstName' ],
    'lastName' => [ Filter\TypeFactory::LINKEDIN, 'lastName' ],
    'fullName' => [ Filter\TypeFactory::COMMON, 'fullName' ],
    'biography' => [ Filter\TypeFactory::FACEBOOK, 'biography' ],
    'gender' => [ Filter\TypeFactory::FACEBOOK, 'gender' ],
    'email' => [ Filter\TypeFactory::LINKEDIN, 'email' ],
    'headline' => [ Filter\TypeFactory::LINKEDIN, 'headline' ],
    'location' => [ Filter\TypeFactory::LINKEDIN, 'location' ],
    'industry' => [ Filter\TypeFactory::LINKEDIN, 'industry' ],
    'summary' => [ Filter\TypeFactory::LINKEDIN, 'summary' ],
    'specialties' => [ Filter\TypeFactory::LINKEDIN, 'specialties' ],
    'interests' => [ Filter\TypeFactory::LINKEDIN, 'interests' ],
    'website' => [ Filter\TypeFactory::FACEBOOK, 'website' ],
    'connections' => [ Filter\TypeFactory::LINKEDIN, 'connections' ]
];

$filterTypes = Helper\Registry::getFilterTypes();

// convert
$query = 'SELECT * FROM `filters`';
foreach ($db->getArray($query) as $record) {

    // not convertible
    if (!isset($map[$record['property']])) {
        continue;
    }

    $descr = $map[$record['property']];

    // method type
    $compType = $filterTypes[$descr[0]]->properties[$descr[1]]['type'];
    if ($compType == Filter\ComparatorFactory::TEXT) {
        $record['method'] = isset($mapTextType[$record['type']])
            ? $mapTextType[$record['type']] : Filter\Comparator\TextComparator::EQUALS;
    } else if ($compType == Filter\ComparatorFactory::NUMERIC) {
        $record['method'] = isset($mapNumericType[$record['type']])
            ? $mapNumericType[$record['type']] : Filter\Comparator\NumericComparator::EQUALS;
    } else {
        continue;
    }

    // type & property
    $record['type'] = $descr[0];
    $record['property'] = $descr[1];

    $query = 'UPDATE `filters`'
        . ' SET `type`= :type,'
        . ' `property`= :property,'
        . ' `method`= :method'
        . ' WHERE `id` = :id';

    $st = $db->getConnection()->prepare($query);
    $st->execute([
        'id' => $record['id'],
        'type' => $record['type'],
        'property' => $record['property'],
        'method' => $record['method']
    ]);
}

// delete all filter we failed to convert
$db->execute('DELETE FROM `filters` WHERE `method` IS NULL');

// disallow NULL on `method`
$query = 'ALTER TABLE `filters` MODIFY `method` TINYINT NOT NULL';
$db->execute($query);
