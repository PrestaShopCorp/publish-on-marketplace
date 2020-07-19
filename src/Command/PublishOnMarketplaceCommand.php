<?php declare(strict_types=1);
/**
 * 2007-2020 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Marketplace\Command;

use Exception;
use PrestaShop\Marketplace\Client\MarketplaceClient;
use PrestaShop\Marketplace\MetadataFile;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PublishOnMarketplaceCommand extends Command
{
    const UPDATE_TYPES = [
        'updatemin',
        'updatemaj',
        'new'
    ];

    /**
     * API Key to use when requesting the marketplace
     *
     * @param string $apiKey
     */
    private $apiKey;

    /**
     * Content of the changelog
     *
     * @param string $changelog
     */
    private $changelog;

    /**
     * Path to product metadata (Technical & display name, PS compatibility range...).
     * 
     * @param array $metadata
     */
    private $metadata;

    /**
     * Path to the archive
     * 
     * @param array $archive
     */
    private $archive;

    /**
     * Reporter in charge of monitoring what is done and provide a complete report
     * at the end of execution
     *
     * @var Reporter
     */
    private $reporter;

    protected function configure()
    {
        $this
            ->setName('prestashop:marketplace:publish')
            ->setDescription('Publish an extension to the marketplace')
            ->addOption(
                'api-key',
                null,
                InputOption::VALUE_OPTIONAL,
                'API Key of the marketplace (Optional if environment variable MARKETPLACE_API_KEY is set)'
            )
            ->addOption(
                'changelog',
                null,
                InputOption::VALUE_REQUIRED,
                'Content of the changelog of the version to upload'
            )
            ->addOption(
                'metadata-json',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to Json file containing details of product'
            )
            ->addOption(
                'archive',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to the archive to upload'
            )
            ->addOption(
                'update-type',
                null,
                InputOption::VALUE_REQUIRED,
                'Type of upgrade (Minor update / Major / new)',
                'updatemin'
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if (getenv('MARKETPLACE_API_KEY')) {
            $this->apiKey = getenv('MARKETPLACE_API_KEY');
        } else {
            $this->apiKey = $input->getOption('api-key');
        }

        if (empty($this->apiKey)) {
            throw new Exception('No API Key is set to authenticate the request to the marketplace. Please set the env var MARKETPLACE_API_KEY or the option --api-key=[...]');
        }

        foreach (['archive', 'changelog', 'metadata-json'] as $option) {
            if (empty($input->getOption($option))) {
                throw new Exception(sprintf('Option --%s must be set.', $option));
            }
        }

        $this->changelog = $input->getOption('changelog');
        $this->archive = $input->getOption('archive');

        if (empty($this->archive) || !file_exists($this->archive) || !is_readable($this->archive)) {
            throw new Exception(sprintf('File %s was not found, or cannot be read', $this->archive));
        }
        $this->metadata = json_decode(
            (new MetadataFile($input->getOption('metadata-json')))->getContent(),
            true
        );

        if (empty($this->metadata)) {
            throw new Exception('Metatada can\'t be loaded; Please check your Json.');
        }

        if (!in_array($input->getOption('update-type'), self::UPDATE_TYPES)) {
            throw new Exception(sprintf(
                'Unrecognized update type "%s", allowed values are: %s',
                $input->getOption('update-type'),
                implode(', ', self::UPDATE_TYPES)
            ));
        }
        $this->metatada['type_upgrade'] = $input->getOption('update-type');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->write(sprintf('The archive (%s) for product #%d is being uploaded... ', $this->metatada['type_upgrade'], $this->metadata['id_product']));
        
        $data = array_merge(
            $this->metadata,
            ['change_log' => $this->changelog]
        );
        $response = (new MarketplaceClient($this->apiKey))->publishExtension($data, $this->archive);
        $output->writeln('Done!');

        $output->writeln($response->getBody()->getContents());
        
        return $response->getStatusCode() === 200 ? 0 : 1;
    }
}
