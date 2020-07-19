<?php

declare(strict_types=1);
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
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PublishOnMarketplaceCommand extends Command
{
    const UPDATE_TYPES = [
        'updatemin',
        'updatemaj',
        'new',
    ];

    /**
     * API Key to use when requesting the marketplace
     *
     * @var string
     */
    private $apiKey;

    /**
     * Content of the changelog
     *
     * @var string
     */
    private $changelog;

    /**
     * Path to product metadata (Technical & display name, PS compatibility range...).
     *
     * @var array
     */
    private $metadata;

    /**
     * Path to the archive
     *
     * @var string
     */
    private $archive;

    protected function configure(): void
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

    protected function initialize(InputInterface $input, OutputInterface $output): void
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
            throw new Exception(sprintf('Unrecognized update type "%s", allowed values are: %s', $input->getOption('update-type'), implode(', ', self::UPDATE_TYPES)));
        }
        $this->metadata['type_upgrade'] = $input->getOption('update-type');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->write(sprintf('The archive (%s) for product #%d is being uploaded... ', $this->metadata['type_upgrade'], $this->metadata['id_product']));

        $data = array_merge(
            $this->metadata,
            ['change_log' => $this->changelog]
        );
        $response = (new MarketplaceClient($this->apiKey))->publishExtension($data, $this->archive);
        $output->writeln('Done!');
        $output->writeln('');

        $this->displayProductUploadDetails($input, $output, $response->getBody()->getContents());

        return $response->getStatusCode() === 200 ? 0 : 1;
    }

    private function displayProductUploadDetails(InputInterface $input, OutputInterface $output, string $responseContents): void
    {
        $decodedResponseContents = json_decode($responseContents, true);

        if (empty($decodedResponseContents['success']) || $decodedResponseContents['success'] !== true) {
            $output->writeln($responseContents);

            return;
        }

        $rows = [];
        foreach ($decodedResponseContents['productUpload'] as $property => $value) {
            $rows[] = [$property, $value];
        }

        $io = new SymfonyStyle($input, $output);
        $io->success('The archive has been succesfully uploaded.');

        $output->writeln('Product upload details:');
        $table = new Table($output);
        $table->setHeaders(['Property', 'Value']);
        $table->setRows($rows);
        $table->render();
    }
}
