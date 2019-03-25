<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ImportService\Model\Import\Processor;

use Magento\Framework\Filesystem\Io\File;
use Magento\ImportService\Api\Data\SourceInterface;
use Magento\ImportService\Api\Data\SourceUploadResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\ImportService\Model\Import\SourceTypePool;
use Magento\ImportService\Model\Source\Validator\ValidatorInterface;
use Magento\ImportService\ImportServiceException;

/**
 * CSV files processor for asynchronous import
 */
class ExternalFileProcessor implements SourceProcessorInterface
{
    /**
     * Import Type
     */
    const IMPORT_TYPE = 'external';

    /**
     * @var PersistentSourceProcessor
     */
    private $persistantUploader;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var array
     */
    private $validators;

    /**
     * LocalPathFileProcessor constructor
     *
     * @param PersistentSourceProcessor $persistantUploader
     * @param Filesystem $fileSystem
     * @param ValidatorInterface[] $validators
     */
    public function __construct(
        PersistentSourceProcessor $persistantUploader,
        Filesystem $fileSystem,
        $validators = []
    ) {
        $this->persistantUploader = $persistantUploader;
        $this->fileSystem = $fileSystem;
        $this->validators = $validators;
    }

    /**
     *  {@inheritdoc}
     */
    public function processUpload(\Magento\ImportService\Api\Data\SourceInterface $source, \Magento\ImportService\Api\Data\SourceUploadResponseInterface $response)
    {
        $errors = [];

        /** check for validations from validators */
        foreach($this->validators as $validator) {
            /** collect errors */
            $errors = array_merge($errors, $validator->validate($source));
        }

        /** throw errros if there is any */
        if(count($errors))
        {
            throw new ImportServiceException(
                __('Invalid request: %1', implode(", ", $errors))
            );
        }

        /** @var \Magento\Framework\Filesystem\Directory\WriteInterface $writeInterface */
        $writeInterface = $this->fileSystem->getDirectoryWrite(DirectoryList::ROOT);

        /** read content from external link */
        $content = $writeInterface->getDriver()->fileGetContents($source->getImportData());

        /** Set downloaded data */
        $source->setImportData($content);

        /** process source and get response details */
        return $this->persistantUploader->processUpload($source, $response);
    }
}
