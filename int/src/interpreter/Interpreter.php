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

use IPP\Interpreter\Loaded\{LoadedProgram};
use IPP\Interpreter\InputModel\{Program, XmlValidationException};
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
    private ?Program $programSource = null;
    private ?LoadedProgram $loadedProgram = null;

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
            $this->programSource = Program::fromXml($rootElement);
        } catch (XmlValidationException $e) {
            throw new InterpreterError(ErrorCode::INT_STRUCTURE, 'Invalid SOL-XML structure', $e);
        }

        $this->loadedProgram = new LoadedProgram($this->programSource);

        if (!$this->loadedProgram->globalScope->hasVariable('Main')) {
            throw new InterpreterError(ErrorCode::SEM_MAIN, 'No Main class found in the program');
        }

        $mainClass = $this->loadedProgram->globalScope->getVariable('Main');

        if ($mainClass->searchMethod('run') === null) {
            throw new InterpreterError(ErrorCode::SEM_MAIN, "No 'run' method found in Main class");
        }
    }

    /**
     * Executes the currently loaded program, using the provided input stream as standard input.
     */
    public function execute(?SplFileObject $inputIo): void
    {
        if ($this->loadedProgram === null) {
            throw new InterpreterError(ErrorCode::INT_OTHER, 'No program is loaded.');
        }

        $this->logger->info('Executing program');

        $mainClass = $this->loadedProgram->globalScope->getVariable('Main');

        $main = new SolObject($mainClass);
        $main->send('run');
    }
}
