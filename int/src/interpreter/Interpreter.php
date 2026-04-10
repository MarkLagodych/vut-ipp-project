<?php

/**
 * This module contains the main logic of the interpreter.
 *
 * Author: Ondrej Ondryas <iondryas@fit.vut.cz>
 * Author: Mark Lagodych <xlagodm00@stud.fit.vut.cz>
 *
 * AI usage notice: The template author used OpenAI Codex to create the implementation of this
 *                  module based on its Python counterpart.
 */

declare(strict_types=1);

namespace IPP\Interpreter;

use IPP\Interpreter\Program;
use IPP\Interpreter\InputModel\{Program as ProgramSource, XmlValidationException};
use IPP\Interpreter\Exception\{InterpreterError, ErrorCode};
use Psr\Log\{NullLogger, LoggerInterface};
use DOMDocument;
use DOMElement;
use SplFileObject;

/**
 * The main interpreter class, responsible for loading the source file and executing the program.
 */
class Interpreter
{
    private LoggerInterface $logger;
    private ?Program $currentProgram = null;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Reads the source SOL-XML file and stores it as the target program for this interpreter.
     * If any program was previously loaded, it is replaced by the new one.
     */
    public function loadProgram(string $sourceFilePath): void
    {
        $this->logger->info(
            'Opening source file: {source_file}',
            ['source_file' => $sourceFilePath]
        );

        $xmlDocument = new DOMDocument();
        $previous = libxml_use_internal_errors(true);

        try {
            if ($xmlDocument->load($sourceFilePath) !== true) {
                throw new InterpreterError(
                    ErrorCode::INT_XML,
                    'Error parsing input XML'
                );
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $rootElement = $xmlDocument->documentElement;
        if (!$rootElement instanceof DOMElement) {
            throw new InterpreterError(ErrorCode::INT_STRUCTURE, 'Invalid SOL-XML structure');
        }

        try {
            $programSource = ProgramSource::fromXml($rootElement);
        } catch (XmlValidationException $e) {
            throw new InterpreterError(ErrorCode::INT_STRUCTURE, 'Invalid SOL-XML structure', $e);
        }

        $this->currentProgram = new Program();
        $this->currentProgram->loadSource($programSource);
    }

    /**
     * Executes the currently loaded program, using the provided input stream as standard input.
     */
    public function execute(?SplFileObject $inputIo): void
    {
        if ($this->currentProgram === null) {
            throw new InterpreterError(ErrorCode::INT_OTHER, 'No program is loaded.');
        }

        $this->logger->info('Executing program');

        $this->currentProgram->run();
    }
}
