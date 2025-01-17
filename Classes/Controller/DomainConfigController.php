<?php
declare(strict_types=1);

namespace ERecht24\Er24Rechtstexte\Controller;


use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/***
 *
 * This file is part of the "eRecht24 Rechtstexte Extension" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020
 *
 ***/

/**
 * DomainConfigController
 */
class DomainConfigController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * @var string
     */
    protected $extensionName = 'er24_rechtstexte';

    /**
     * domainConfigRepository
     *
     * @var \ERecht24\Er24Rechtstexte\Domain\Repository\DomainConfigRepository
     */
    protected $domainConfigRepository = null;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     */
    protected $persistenceManager = null;

    /**
     * @var \ERecht24\Er24Rechtstexte\Utility\ApiUtility
     */
    protected $apiUtility = null;

    /**
     * @param \ERecht24\Er24Rechtstexte\Utility\ApiUtility $apiUtility
     */
    public function injectApiUtility(\ERecht24\Er24Rechtstexte\Utility\ApiUtility $apiUtility)
    {
        $this->apiUtility = $apiUtility;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager $persistenceManager
     */
    public function injectPersistenceManager(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * @param \ERecht24\Er24Rechtstexte\Domain\Repository\DomainConfigRepository $domainConfigRepository
     */
    public function injectDomainConfigRepository(\ERecht24\Er24Rechtstexte\Domain\Repository\DomainConfigRepository $domainConfigRepository)
    {
        $this->domainConfigRepository = $domainConfigRepository;
    }

    /**
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function performUpdateAction()
    {
        $updateUtility = new \ERecht24\Er24Rechtstexte\Utility\UpdateUtility();
        if (true === $updateUtility->performSelfUpdate()) {
            $this->addFlashMessage(LocalizationUtility::translate('message-prefix', $this->extensionName) . LocalizationUtility::translate('update-success', $this->extensionName), '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        } else {
            $this->addFlashMessage(LocalizationUtility::translate('message-prefix', $this->extensionName) . LocalizationUtility::translate('update-failed', $this->extensionName), '', \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING);
        }
        $this->redirect('list');
    }

    /**
     * action list
     *
     * @return void
     */
    public function listAction()
    {

        $jsRequiredLanguageKeys = [
            'attention',
            'delete-confirm',
            'abort'
        ];

        $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);

        foreach ($jsRequiredLanguageKeys as $key) {
            $label = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'er24_rechtstexte');
            $pageRenderer->addInlineLanguageLabel(str_replace('-', '_', $key), $label);
        }

        $updateUtility = new \ERecht24\Er24Rechtstexte\Utility\UpdateUtility();

        /** @var \TYPO3\CMS\Core\Site\SiteFinder $siteFinder */
        $siteFinder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Site\SiteFinder::class);

        $allSiteConfigurations = $siteFinder->getAllSites();
        $domainConfigs = $this->domainConfigRepository->findAll();

        $domainsLeft = $configuredDomains = [];

        /** @var \TYPO3\CMS\Core\Site\Entity\Site $siteConfig */
        foreach ($allSiteConfigurations as $index => $siteConfig) {
            $domainsLeft[(string)$siteConfig->getBase()] = $index;
        }

        /** @var \ERecht24\Er24Rechtstexte\Domain\Model\DomainConfig $config */
        foreach ($domainConfigs as $config) {
            $configuredDomains[$config->getDomain()] = $config->getDomain();
//            $urlParts = parse_url($config->getDomain());
//            if ($urlParts !== false) {
//
//            }
            if (true === isset($domainsLeft[$config->getDomain()])) {
                unset($domainsLeft[$config->getDomain()]);
            }
        }

        /** @var \TYPO3\CMS\Core\Site\Entity\Site $siteConfig */
        foreach ($allSiteConfigurations as $index => $siteConfig) {
            $match = false;
            foreach ($domainsLeft as $domain => $siteIdentifier) {
                if ($index === $siteIdentifier) {
                    $match = true;
                }
            }
            if ($match === false) {
                unset($allSiteConfigurations[$index]);
            }
        }

        $this->view->assignMultiple([
            'domainConfigs' => $domainConfigs,
            'allSiteConfigurations' => $allSiteConfigurations,
            'configuredDomains' => $configuredDomains,
            'updateAvailable' => $updateUtility->updateAvailable,
            'latestVersion' => $updateUtility->latestVersion,
            'composerMode' => $updateUtility->composeMode
        ]);
    }

    /**
     * action show
     * @return void
     */
    public function showAction()
    {
        /** @var \ERecht24\Er24Rechtstexte\Domain\Model\DomainConfig $domainConfig */
        $domainConfig = $this->domainConfigRepository->findByUid($this->settings['configId']);
        if ($domainConfig === null) {
            // TODO
            $this->addFlashMessage(LocalizationUtility::translate('configuration-not-found', $this->extensionName), '', \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING);
            return $this->view->render();
        }


        $language = $this->settings['documentLanguage'];

        switch ($this->settings['documentType']) {
            case 'imprint':
                $source = $domainConfig->getImprintSource() === 0 ? 'Local' : '';
                break;
            case 'privacy':
                $source = $domainConfig->getPrivacySource() === 0 ? 'Local' : '';
                break;
            case 'social':
                $source = $domainConfig->getSocialSource() === 0 ? 'Local' : '';
        }

        $getterFunctionName = 'get' . ucfirst($this->settings['documentType']) . $language . $source;

        if (method_exists($domainConfig, $getterFunctionName)) {
            $outputText = $domainConfig->$getterFunctionName();

            if (true === (bool)$this->settings['removeHeadline'] && strpos($outputText, '</h1>') !== false) {
                $outputText = substr($outputText, strpos($outputText, '</h1>') + 5);
            }

            // check if outputText isn't empty
            if (strlen(trim($outputText)) > 0) {
                // replace emails with TYPO3 spambot safe links
                // try to get it working with not normalized domain names
                // please use idn syntax: https://de.wikipedia.org/wiki/Internationalisierter_Domainname
                $mailRegex = "/([-0-9a-zA-Z.+_äöüßÄÖÜéèê]+@[-0-9a-zA-Z.+_äöüßÄÖÜéèê]+.[a-zA-Z])/";
                preg_match_all($mailRegex, $outputText, $matches);

                foreach ($matches[0] as $match) {
                    $outputText = str_replace($match, $this->createEmailLink($match), $outputText);
                }
            }

            $GLOBALS['TSFE']->addCacheTags(['er24_document_' . $domainConfig->getUid()]);

            $this->view->assignMultiple([
                'outputText' => $outputText
            ]);
        } else {
            $this->addFlashMessage(LocalizationUtility::translate('document-not-found', $this->extensionName), '', \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING);
        }

    }

    private function createEmailLink(string $email)
    {
        if (version_compare(VersionNumberUtility::getNumericTypo3Version(), "12.0.0", "<")) {
            [$linkHref, $linkText] = $GLOBALS['TSFE']->cObj->getMailTo($email, '');
            return "<a href='" . $linkHref . "'>" . $linkText . "</a>";
        } else {
            // TODO: implement for v12
            // https://github.com/TYPO3/typo3/blob/main/typo3/sysext/frontend/Classes/ContentObject/ContentObjectRenderer.php#L4663
        }
    }

    /**
     * @param \ERecht24\Er24Rechtstexte\Domain\Model\DomainConfig|null $newDomainConfig
     * @param string $siteconfigIdentifier
     */
    public function newAction(\ERecht24\Er24Rechtstexte\Domain\Model\DomainConfig $newDomainConfig = null, string $siteconfigIdentifier = '')
    {

        /** @var \TYPO3\CMS\Core\Site\SiteFinder $siteFinder */
        $siteFinder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Site\SiteFinder::class);


        if ($newDomainConfig === null) {
            $newDomainConfig = new \ERecht24\Er24Rechtstexte\Domain\Model\DomainConfig();
            $newDomainConfig->setSiteConfigName($siteconfigIdentifier);
        }

        if ($newDomainConfig->getSiteConfigName() !== '') {
            try {
                $siteConfig = $siteFinder->getSiteByIdentifier($newDomainConfig->getSiteConfigName());
                $newDomainConfig->setDomain((string)$siteConfig->getBase());
            } catch (\Exception $e) {
                $siteConfig = $language = null;
            }
        }

        $allSites = $siteFinder->getAllSites();
        $allDomainConfigs = $this->domainConfigRepository->findAll();

        // Remove already used Siteconfigs
        /** @var \ERecht24\Er24Rechtstexte\Domain\Model\DomainConfig $domainConfig */
        foreach ($allDomainConfigs as $domainConfig) {
            if (true === array_key_exists($domainConfig->getSiteConfigName(), $allSites)) {
                unset($allSites[$domainConfig->getSiteConfigName()]);
            }
        }

        $this->view->assignMultiple([
            'newDomainConfig' => $newDomainConfig,
            'siteConfig' => $siteConfig,
            'allSiteConfigurations' => $allSites
        ]);
    }

    /**
     * action create
     *
     * @param \ERecht24\Er24Rechtstexte\Domain\Model\DomainConfig $newDomainConfig
     * @return void
     */
    public function createAction(\ERecht24\Er24Rechtstexte\Domain\Model\DomainConfig $newDomainConfig)
    {

        $this->addFlashMessage(LocalizationUtility::translate('message-prefix', $this->extensionName) . ' ' . \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('config-was-created', $this->extensionName), '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);

        $now = time();

        $newDomainConfig->setSocialEnTstamp($now);
        $newDomainConfig->setSocialDeTstamp($now);
        $newDomainConfig->setImprintEnTstamp($now);
        $newDomainConfig->setImprintDeTstamp($now);
        $newDomainConfig->setPrivacyEnTstamp($now);
        $newDomainConfig->setPrivacyDeTstamp($now);

        $this->domainConfigRepository->add($newDomainConfig);
        $this->persistenceManager->persistAll();

        if ($newDomainConfig->getSiteConfigName() !== '') {
            /** @var \TYPO3\CMS\Core\Configuration\SiteConfiguration $siteConfiguration */
            $siteConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\SiteConfiguration::class);
            $configurationArray = $siteConfiguration->load($newDomainConfig->getSiteConfigName());
            $configurationArray['eRecht24Config'] = $newDomainConfig->getUid();
            $siteConfiguration->write($newDomainConfig->getSiteConfigName(), $configurationArray);
        }

        $this->redirect('edit', null, null, ['domainConfig' => $newDomainConfig->getUid()]);

    }

    /**
     * action edit
     *
     * @param \ERecht24\Er24Rechtstexte\Domain\Model\DomainConfig $domainConfig
     * @TYPO3\CMS\Extbase\Annotation\IgnoreValidation("domainConfig")
     * @return void
     */
    public function editAction(\ERecht24\Er24Rechtstexte\Domain\Model\DomainConfig $domainConfig)
    {

        $jsRequiredLanguageKeys = [
            'connection_error_detected',
            'message-prefix',
            'attention',
            'delete-confirm',
            'delete',
            'abort',
            'debug-was-copied',
            'error'
        ];

        $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);

        foreach ($jsRequiredLanguageKeys as $key) {
            $label = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'er24_rechtstexte');
            $pageRenderer->addInlineLanguageLabel(str_replace('-', '_', $key), $label);
        }

        $errors = $pushError = $configError = $erechtServerError = $curlError = false;
        $configErrorMessages = [];

        $updateUtility = new \ERecht24\Er24Rechtstexte\Utility\UpdateUtility();

        if ($domainConfig->getClientId() !== '') {
            $client = new \ERecht24\Er24Rechtstexte\Api\Client($domainConfig->getApiKey(), $domainConfig->getDomain());
            $apiResponse = $client->testPushPing((int)$domainConfig->getClientId());
            if ($apiResponse->isSuccess() === false) {
                $pushError = true;
                $errors = true;
            }

        } else {
            $errors = $configError = $pushError = true;
            $configErrorMessages[] = 'noclient_exists';
        }

        if ($domainConfig->getApiKey() === '') {
            $configErrorMessages[] = 'noapikey_exists';
        } else {
            $client = new \ERecht24\Er24Rechtstexte\Api\Client($domainConfig->getApiKey(), $domainConfig->getDomain());
            $apiResponse = $client->listClients();
            if ($apiResponse->isSuccess() === false) {
                $erechtServerError = true;
            }
        }

        $curlError = function_exists('curl_version') ? false : true;

        $debugInformations = 'PHP Version: ' . phpversion() . PHP_EOL;
        $debugInformations .= 'TYPO3 Composer Mode: ' . (int)\TYPO3\CMS\Core\Core\Environment::isComposerMode() . PHP_EOL;
        $debugInformations .= 'cURL Error: ' . (int)$curlError . PHP_EOL;
        $debugInformations .= 'Push Error: ' . (int)$pushError . PHP_EOL;
        $debugInformations .= 'API Connection Error: ' . (int)$erechtServerError . PHP_EOL;
        $debugInformations .= 'Configuration Errors: ' . (int)$configError . PHP_EOL;

        if (count($configErrorMessages) > 0) {
            $debugInformations .= 'Configuration Error Details: ' . PHP_EOL;
            foreach ($configErrorMessages as $error) {
                $debugInformations .= $error . PHP_EOL;
            }
        }

        $debugInformations .= PHP_EOL;

        $debugInformations .= 'API Key: ' . substr($domainConfig->getApiKey(), 0, 30) . '...' . PHP_EOL;
        $debugInformations .= 'Client ID: ' . $domainConfig->getClientId() . PHP_EOL;
        $debugInformations .= 'Client Secret: ' . substr($domainConfig->getClientSecret(), 0, 30) . '...' . PHP_EOL;
        $debugInformations .= 'API Host: ' . \ERecht24\Er24Rechtstexte\Utility\HelperUtility::API_HOST_URL . PHP_EOL;
        $debugInformations .= 'API Push URI: ' . $domainConfig->getDomain() . 'erecht24/v1/push' . PHP_EOL;
        $debugInformations .= PHP_EOL;
        $debugInformations .= 'Error Log:' . PHP_EOL;
        $debugInformations .= \ERecht24\Er24Rechtstexte\Utility\LogUtility::getErrorLog();
        $debugInformations .= PHP_EOL;
        $debugInformations .= 'Extension informations:' . PHP_EOL;


        /** @var PackageManager $packageManager */
        $packageManager = GeneralUtility::makeInstance(PackageManager::class);

        foreach ($packageManager->getActivePackages() as $extension) {
            $debugInformations .= $extension->getPackageKey() . ' (' . $extension->getPackageMetaData()->getVersion() . ')' . PHP_EOL;
        }

        // The Docs //
        require_once(ExtensionManagementUtility::extPath('er24_rechtstexte') . 'Resources/Private/Vendor/Erusev/Parsedown/Parsedown.php');

        $parseDown = new \Parsedown();

        if ($GLOBALS['BE_USER']->uc['lang'] === 'de') {
            $documentation = (string)$parseDown->text(file_get_contents(ExtensionManagementUtility::extPath('er24_rechtstexte') . 'Documentation/Documentation_de.md'));
        } else {
            $documentation = (string)$parseDown->text(file_get_contents(ExtensionManagementUtility::extPath('er24_rechtstexte') . 'Documentation/Documentation_en.md'));
        }


        $this->view->assignMultiple([
            'domainConfig' => $domainConfig,
            'errors' => $errors,
            'pushError' => $pushError,
            'erechtServerError' => $erechtServerError,
            'configError' => $configError,
            'configErrorMessages' => $configErrorMessages,
            'curlError' => $curlError,
            'debugInformations' => $debugInformations,
            'documentation' => $documentation,
            't3version' => VersionNumberUtility::convertVersionNumberToInteger(VersionNumberUtility::getNumericTypo3Version())
        ]);
    }

    /**
     * action update
     *
     * @param \ERecht24\Er24Rechtstexte\Domain\Model\DomainConfig $domainConfig
     * @return void
     */
    public function updateAction(\ERecht24\Er24Rechtstexte\Domain\Model\DomainConfig $domainConfig)
    {

        $apiHandlerResult = $this->apiUtility->handleDomainConfigUpdate($domainConfig, $domainConfig->getApiKey());
        self::handleApiHandlerResults($apiHandlerResult);

        if ($domainConfig->getImprintSource() === null) $domainConfig->setImprintSource(0);
        if ($domainConfig->getSocialSource() === null) $domainConfig->setSocialSource(0);
        if ($domainConfig->getPrivacySource() === null) $domainConfig->setPrivacySource(0);

        $this->domainConfigRepository->update($domainConfig);
        $this->persistenceManager->persistAll();

        /** @var \TYPO3\CMS\Core\Cache\CacheManager $cacheManager */
        $cacheManager = $this->objectManager->get(\TYPO3\CMS\Core\Cache\CacheManager::class);
        $cacheManager->flushCachesByTag('er24_document_' . $domainConfig->getUid());

        $this->redirect('edit', null, null, ['domainConfig' => $domainConfig->getUid()]);
    }

    protected function handleApiHandlerResults($apiHandlerResult)
    {
        if (count($apiHandlerResult[0]) > 0) {
            foreach ($apiHandlerResult[0] as $error) {
                $this->addFlashMessage(LocalizationUtility::translate('message-prefix', $this->extensionName) . ' ' . $error, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING);
            }
        }
        if (count($apiHandlerResult[1]) > 0) {
            foreach ($apiHandlerResult[1] as $success) {
                $this->addFlashMessage(LocalizationUtility::translate('message-prefix', $this->extensionName) . ' ' . $success, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
            }
        }
    }

    /**
     * action delete
     * @param \ERecht24\Er24Rechtstexte\Domain\Model\DomainConfig $domainConfig
     * @return void
     */
    public function deleteAction(\ERecht24\Er24Rechtstexte\Domain\Model\DomainConfig $domainConfig)
    {

        if ($domainConfig->getClientId() !== '' && $domainConfig->getApiKey() !== '') {
            $apiHandlerResult = $this->apiUtility->deleteDomainConfigClient($domainConfig, $domainConfig->getApiKey());
            self::handleApiHandlerResults($apiHandlerResult);
        }

        // Remove from SiteConfig
        if ($domainConfig->getSiteConfigName() !== '') {
            // TODO: Take care of renamed site configs
            /** @var \TYPO3\CMS\Core\Configuration\SiteConfiguration $siteConfiguration */
            $siteConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\SiteConfiguration::class);
            $configurationArray = $siteConfiguration->load($domainConfig->getSiteConfigName());
            unset($configurationArray['eRecht24Config']);
            $siteConfiguration->write($domainConfig->getSiteConfigName(), $configurationArray);
        }

        $this->addFlashMessage(LocalizationUtility::translate('message-prefix', $this->extensionName) . ' ' . \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('config-was-deleted', $this->extensionName), '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);
        $this->domainConfigRepository->remove($domainConfig);
        $this->redirect('list');
    }
}
