<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
function autoload66b2f12b5363087bcc01dd29b3ad78ba($class) {
    static $classes = null;
    if ($classes === null) {
        $classes = array(
            'cacheids' => '/rest/RestBase.php',
            'databaseinitialization' => '/DatabaseInitialisation.class.php',
            'dbtestaccess' => '/DBTestAccess.php',
            'fakedataaccessresult' => '/TestHelper.class.php',
            'ongoingintelligentstub' => '/MockBuilder.php',
            'rest_databaseinitialization' => '/rest/DatabaseInitialization.class.php',
            'rest_testdatabuilder' => '/rest/TestDataBuilder.php',
            'restbase' => '/rest/RestBase.php',
            'signaturemapwithdefault' => '/MockBuilder.php',
            'soap_databaseinitialization' => '/soap/DatabaseInitialization.php',
            'soap_testdatabuilder' => '/soap/TestDataBuilder.php',
            'soapbase' => '/soap/SOAPBase.php',
            'test\\rest\\requestwrapper' => '/rest/RequestWrapper.php',
            'test\\rest\\tracker\\tracker' => '/rest/tracker/Tracker.php',
            'test\\rest\\tracker\\trackerfactory' => '/rest/tracker/TrackerFactory.php',
            'testdatabuilder' => '/TestDataBuilder.php',
            'testhelper' => '/TestHelper.class.php',
            'tuleap\\rest\\artifactbase' => '/rest/ArtifactBase.php',
            'tuleap\\rest\\artifactfilebase' => '/rest/ArtifactFileBase.php',
            'tuleap\\rest\\cardsbase' => '/rest/CardsBase.php',
            'tuleap\\rest\\milestonebase' => '/rest/MilestoneBase.php',
            'tuleap\\rest\\projectbase' => '/rest/ProjectBase.php',
            'tuleap\\rest\\trackerbase' => '/rest/TrackerBase.php',
            'tuleapdbtestcase' => '/TuleapDbTestCase.class.php',
            'tuleaperrortrappinginvoker' => '/TuleapErrorTrappingInvoker.class.php',
            'tuleaptestcase' => '/TuleapTestCase.class.php',
            'unimplementedthrower' => '/MockBuilder.php'
        );
    }
    $cn = strtolower($class);
    if (isset($classes[$cn])) {
        require dirname(__FILE__) . $classes[$cn];
    }
}
spl_autoload_register('autoload66b2f12b5363087bcc01dd29b3ad78ba');
// @codeCoverageIgnoreEnd
