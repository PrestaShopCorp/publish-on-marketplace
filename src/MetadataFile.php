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

namespace PrestaShop\Marketplace;

/**
 * Class responsible of loading metadata file in memory and returning its content
 */
class MetadataFile
{
    /**
     * Header content
     *
     * @var string
     */
    private $content;

    /**
     * Path to the file
     *
     * @var string
     */
    private $filePath;

    /**
     * @param string $filePath
     */
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->content = $this->loadFile();
    }

    /**
     * @return string Getter for Metadata content
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Checks the file and loads its content in memory
     */
    private function loadFile(): string
    {
        if (!\file_exists($this->filePath)) {
            // If the file is not found, we might have a relative path
            // We check this before throwing any exception
            $fromRelativeFilePath = getcwd() . '/' . $this->filePath;
            $fromSrcFolderFilePath = __DIR__ . '/../' . $this->filePath;

            if (\file_exists($fromRelativeFilePath)) {
                $this->filePath = $fromRelativeFilePath;
            } elseif (\file_exists($fromSrcFolderFilePath)) {
                $this->filePath = $fromSrcFolderFilePath;
            } else {
                throw new \Exception('File ' . $this->filePath . ' does not exist.');
            }
        }

        if (!\is_readable($this->filePath)) {
            throw new \Exception('File ' . $this->filePath . ' cannot be read.');
        }

        return (string) \file_get_contents($this->filePath);
    }
}
