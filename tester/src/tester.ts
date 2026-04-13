#!/usr/bin/env node
/**
 * An integration testing script for the SOL26 interpreter.
 *
 * IPP: You can implement the entire tool in this file if you wish, but it is recommended to split
 *      the code into multiple files and modules as you see fit.
 *
 *      Below, you have some code to get you started with the CLI argument parsing and logging setup,
 *      but you are **free to modify it** in whatever way you like.
 *
 * Author: Ondřej Ondryáš <iondryas@fit.vut.cz>
 *
 * AI usage notice: The author used OpenAI Codex to create the implementation of this
 *                  module based on its Python counterpart.
 */

import { existsSync, lstatSync, writeFileSync, readdirSync, readFileSync } from "node:fs";
import { dirname, basename, resolve } from "node:path";
import { parseArgs } from "node:util";
import { spawnSync } from "node:child_process";

import {
  CategoryReport,
  TestCaseDefinition,
  TestCaseReport,
  TestCaseType,
  TestReport,
  TestResult,
  UnexecutedReason,
  UnexecutedReasonCode,
} from "./models.js";

import { pino } from "pino";

const logger = pino({
  transport: {
    target: "pino-pretty",
    options: {
      colorize: true,
      destination: 2,
    },
  },
});

interface CliArguments {
  tests_dir: string;
  recursive: boolean;
  output: string | null;
  dry_run: boolean;
  include: string[] | null;
  include_category: string[] | null;
  include_test: string[] | null;
  exclude: string[] | null;
  exclude_category: string[] | null;
  exclude_test: string[] | null;
  verbose: number;
  regex_filters: boolean;
}

function writeResult(resultReport: TestReport, outputFile: string | null): void {
  /**
   * Writes the final report to the specified output file or standard output if no file is provided.
   */
  const resultJson = JSON.stringify(resultReport, null, 2);
  if (outputFile !== null) {
    writeFileSync(outputFile, resultJson, "utf8");
    return;
  }

  console.log(resultJson);
}

const DOUBLE_LETTER_SHORT_OPTION_NORMALIZATION = new Map<string, string>([
  ["-ic", "--include-category"],
  ["-it", "--include-test"],
  ["-ec", "--exclude-category"],
  ["-et", "--exclude-test"],
]);

const HELP_TEXT = [
  "Usage:",
  "  tester [options] tests_dir",
  "",
  "Positional arguments:",
  "  tests_dir                 Path to a directory with the test cases in the SOLtest format.",
  "",
  "Options:",
  "  -h, --help                Show this help message and exit.",
  "  -r, --recursive           Recursively search for test cases in subdirectories of the provided directory.",
  "  -o, --output <path>       The output file to write the test results to. If not provided, results will be printed to standard output.",
  "  --dry-run                 Perform a dry run: discover the test cases but don't actually execute them.",
  "  -i, --include <value>     Include only test cases with the specified name or category. Can be used multiple times to specify multiple criteria.Can be combined with -ic and -it.",
  "  -ic, --include-category <value>",
  "                            Include only test cases with the specified category. Can be used multiple times to specify multiple accepted categories. Can be combined with -it and -i.",
  "  -it, --include-test <value>",
  "                            Include only test cases with the specified name. Can be used multiple times to specify multiple accepted names. Can be combined with -ic and -i.",
  "  -e, --exclude <value>     Exclude test cases with the specified name or category. Can be used multiple times to specify multiple criteria.Can be combined with -ic and -it.",
  "  -ec, --exclude-category <value>",
  "                            Exclude test cases with the specified category. Can be used multiple times to specify multiple accepted categories. Can be combined with -it and -i.",
  "  -et, --exclude-test <value>",
  "                            Exclude test cases with the specified name. Can be used multiple times to specify multiple accepted names. Can be combined with -ic and -i.",
  "  -g                        When used, the filters specified with -i[ct]/-e[ct] will be interpreted as regular expressions instead of literal strings.",
  "  -v, --verbose             Enable verbose logging output (using once = INFO level, using twice = DEBUG level).",
];

const PARSE_OPTIONS = {
  help: { type: "boolean", short: "h", default: false },
  recursive: { type: "boolean", short: "r", default: false },
  output: { type: "string", short: "o" },
  "dry-run": { type: "boolean", default: false },
  include: { type: "string", short: "i", multiple: true },
  "include-category": { type: "string", multiple: true },
  "include-test": { type: "string", multiple: true },
  exclude: { type: "string", short: "e", multiple: true },
  "exclude-category": { type: "string", multiple: true },
  "exclude-test": { type: "string", multiple: true },
  "regex-filters": { type: "boolean", short: "g", default: false },
  verbose: { type: "boolean", short: "v", multiple: true },
} as const;

function normalizeArgv(argv: string[]): string[] {
  return argv.map((arg) => DOUBLE_LETTER_SHORT_OPTION_NORMALIZATION.get(arg) ?? arg);
}

function printHelp(): void {
  console.log(HELP_TEXT.join("\n"));
}

function listOrNull(values: string[] | undefined): string[] | null {
  if (values === undefined || values.length === 0) {
    return null;
  }

  return values;
}

function parseCliArgumentsRaw(argv: string[]) {
  return parseArgs({
    args: normalizeArgv(argv),
    options: PARSE_OPTIONS,
    allowPositionals: true,
    strict: true,
  } as const);
}

function parseArguments(): CliArguments {
  /**
   * Parses the command-line arguments and performs basic validation a sanitization.
   */
  let parsed: ReturnType<typeof parseCliArgumentsRaw>;

  try {
    parsed = parseCliArgumentsRaw(process.argv.slice(2));
  } catch (error: unknown) {
    const message = error instanceof Error ? error.message : String(error);
    console.error(message);
    process.exit(2);
  }

  const parsedValues = parsed.values;

  if (parsedValues["help"]) {
    printHelp();
    process.exit(0);
  }

  if (parsed.positionals.length !== 1 || parsed.positionals[0] === undefined) {
    console.error("Exactly one positional argument (tests_dir) is required.");
    process.exit(2);
  }

  const args: CliArguments = {
    tests_dir: resolve(parsed.positionals[0]),
    recursive: parsedValues["recursive"],
    output: parsedValues["output"] ?? null,
    dry_run: parsedValues["dry-run"],
    include: listOrNull(parsedValues["include"]),
    include_category: listOrNull(parsedValues["include-category"]),
    include_test: listOrNull(parsedValues["include-test"]),
    exclude: listOrNull(parsedValues["exclude"]),
    exclude_category: listOrNull(parsedValues["exclude-category"]),
    exclude_test: listOrNull(parsedValues["exclude-test"]),
    verbose: parsedValues["verbose"]?.length ?? 0,
    regex_filters: parsedValues["regex-filters"],
  };

  // Check source directory
  if (!existsSync(args.tests_dir) || !lstatSync(args.tests_dir).isDirectory()) {
    console.error("The provided path is not a directory.");
    process.exit(1);
  }

  // Warn if the output file already exists
  if (args.output !== null) {
    const outputParent = dirname(args.output);
    if (!existsSync(outputParent)) {
      console.error("The parent directory of the output file does not exist.");
      process.exit(1);
    }

    if (existsSync(args.output)) {
      logger.warn("The output file will be overwritten: %s", args.output);
    }
  }

  return args;
}

/**
 * Searches for `.test` files in the given directory.
 * Returns a list of paths to found files.
 */
function searchTests(dir: string, recursive: boolean): string[] {
  const entries = readdirSync(dir, { withFileTypes: true, recursive: recursive })
    .filter((entry) => entry.isFile() && entry.name.endsWith(".test"))
    .map((entry) => resolve(entry.parentPath, entry.name));
  return entries;
}

interface TestFiles {
  name: string;
  test_source_path: string;
  stdin_file: string | null;
  expected_stdout_file: string | null;
}

/**
 * Gathers basic test info based on the path of the main `.test` file.
 */
function findTestFiles(test_file: string): TestFiles {
  const name = basename(test_file, ".test");
  const dir = dirname(test_file);
  const stdin_file = resolve(dir, name + ".in");
  const expected_stdout_file = resolve(dir, name + ".out");

  return {
    name,
    test_source_path: test_file,
    stdin_file: existsSync(stdin_file) ? stdin_file : null,
    expected_stdout_file: existsSync(expected_stdout_file) ? expected_stdout_file : null,
  };
}

interface TestInfo {
  test_type: TestCaseType;
  description: string | null;
  category: string;
  points: number;
  expected_parser_exit_codes: number[] | null;
  expected_interpreter_exit_codes: number[] | null;
  source: string;
}

function splitOnce(str: string, delimiter: string): [string, string] {
  const index = str.indexOf(delimiter);
  if (index === -1) {
    return [str, ""];
  }

  return [str.slice(0, index), str.slice(index + delimiter.length)];
}

function getTestType(parserCodes: number[], interpreterCodes: number[]): TestCaseType {
  if (parserCodes.length > 0 && interpreterCodes.length > 0) {
    return TestCaseType.COMBINED;
  } else if (parserCodes.length > 0) {
    return TestCaseType.PARSE_ONLY;
  } else {
    return TestCaseType.EXECUTE_ONLY;
  }
}

function readTestInfo(test_file: string): TestInfo | null {
  const src = readFileSync(test_file, "utf8");

  let description: string | null = null;
  let category: string | null = null;
  let points: number | null = null;
  let source: string | null = null;
  const parser_codes: number[] = [];
  const interpreter_codes: number[] = [];

  const lines = src.split("\n");
  for (const [i, line] of lines.entries()) {
    if (line.length === 0) {
      source = lines.slice(i + 1).join("\n");
      break;
    }

    const [marker, text] = splitOnce(line, " ");

    switch (marker) {
      case "***":
        description = text;
        break;
      case "+++":
        category = text;
        break;
      case "!C!":
        parser_codes.push(Number(text));
        break;
      case "!I!":
        interpreter_codes.push(Number(text));
        break;
      case ">>>":
        points = Number(text);
        break;
    }
  }

  if (
    category === null ||
    points === null ||
    source === null ||
    (parser_codes.length === 0 && interpreter_codes.length === 0)
  ) {
    return null;
  }

  return {
    test_type: getTestType(parser_codes, interpreter_codes),
    description,
    category,
    points,
    expected_parser_exit_codes: parser_codes.length == 0 ? null : parser_codes,
    expected_interpreter_exit_codes: interpreter_codes.length == 0 ? null : interpreter_codes,
    source,
  };
}

class Test {
  public readonly def: TestCaseDefinition;
  public readonly source: string;
  public readonly stdin_file: string | null;
  public readonly expected_stdout_file: string | null;

  public constructor(test_files: TestFiles, test_info: TestInfo) {
    this.def = new TestCaseDefinition({
      ...test_files,
      ...test_info,
    });

    this.source = test_info.source;

    this.stdin_file = test_files.stdin_file;
    this.expected_stdout_file = test_files.expected_stdout_file;
  }

  public needsParser(): boolean {
    return (
      this.def.test_type === TestCaseType.PARSE_ONLY ||
      this.def.test_type === TestCaseType.COMBINED
    );
  }

  public needsInterpreter(): boolean {
    return (
      this.def.test_type === TestCaseType.EXECUTE_ONLY ||
      this.def.test_type === TestCaseType.COMBINED
    );
  }
}

function filterTest(test: Test, args: CliArguments): boolean {
  if (args.include !== null)
    if (!args.include.includes(test.def.name) && !args.include.includes(test.def.category)) {
      return false;
    }

  if (args.exclude !== null)
    if (args.exclude.includes(test.def.name) || args.exclude.includes(test.def.category)) {
      return false;
    }

  if (args.include_test !== null)
    if (!args.include_test.includes(test.def.name)) {
      return false;
    }

  if (args.exclude_test !== null)
    if (args.exclude_test.includes(test.def.name)) {
      return false;
    }

  if (args.include_category !== null)
    if (!args.include_category.includes(test.def.category)) {
      return false;
    }

  if (args.exclude_category !== null)
    if (args.exclude_category.includes(test.def.category)) {
      return false;
    }

  return true;
}

function discoverTests(args: CliArguments): Test[] {
  const testFiles = searchTests(args.tests_dir, args.recursive);
  const tests: Test[] = [];

  for (const testFile of testFiles) {
    const test_files = findTestFiles(testFile);
    const test_info = readTestInfo(testFile);

    if (test_info === null) {
      logger.info("Skipping test file with invalid format: %s", testFile);
      continue;
    }

    const test = new Test(test_files, test_info);

    tests.push(test);
  }

  return tests;
}

const SOL2XML = resolve(import.meta.dirname, "../sol2xml/sol_to_xml.py");
const SOLINT = resolve(import.meta.dirname, "../../int/src/solint.php");
const TMP_XML = resolve(import.meta.dirname, "../.tmp.xml");

function executeTest(
  test: Test,
  reports: Record<string, CategoryReport>,
  unexecuted: Record<string, UnexecutedReason>
) {
  let parserResult = null;
  let interpreterResult = null;
  let diffResult = null;

  if (test.needsParser()) {
    try {
      parserResult = spawnSync(SOL2XML, {
        input: test.source,
        encoding: "utf8",
      });
    } catch {
      unexecuted[test.def.name] = new UnexecutedReason(
        UnexecutedReasonCode.CANNOT_EXECUTE,
        "Failed to execute the parser"
      );
      return;
    }
  }

  if (test.needsInterpreter()) {
    const source = parserResult?.stdout ?? test.source;
    writeFileSync(TMP_XML, source, "utf8");

    const input_args = test.stdin_file !== null ? ["-i", test.stdin_file] : [];

    try {
      interpreterResult = spawnSync("php", [SOLINT, "-s", TMP_XML, ...input_args], {
        input: source,
        encoding: "utf8",
      });
    } catch {
      unexecuted[test.def.name] = new UnexecutedReason(
        UnexecutedReasonCode.CANNOT_EXECUTE,
        "Failed to execute the interpreter"
      );
      return;
    }

    if (test.expected_stdout_file !== null) {
      diffResult = spawnSync("diff", [test.expected_stdout_file, "-"], {
        input: interpreterResult.stdout,
        encoding: "utf8",
      });
    }
  }

  writeReport(test, reports, parserResult, interpreterResult, diffResult);
}

function getTestResult(
  test: Test,
  parserResult: ReturnType<typeof spawnSync> | null,
  interpreterResult: ReturnType<typeof spawnSync> | null,
  diffResult: ReturnType<typeof spawnSync> | null
): TestResult {
  if (parserResult !== null && parserResult.status != null) {
    if (!test.def.expected_parser_exit_codes?.includes(parserResult.status)) {
      return TestResult.UNEXPECTED_PARSER_EXIT_CODE;
    }
  } else if (interpreterResult !== null && interpreterResult.status != null) {
    if (!test.def.expected_interpreter_exit_codes?.includes(interpreterResult.status)) {
      return TestResult.UNEXPECTED_INTERPRETER_EXIT_CODE;
    }
  } else if (diffResult !== null && diffResult.status !== 0) {
    return TestResult.INTERPRETER_RESULT_DIFFERS;
  }

  return TestResult.PASSED;
}

interface MutableCategoryReport {
  total_points: number;
  passed_points: number;
  test_results: Record<string, TestCaseReport>;
}

function getTestReport(
  test_result: TestResult,
  parserResult: ReturnType<typeof spawnSync> | null,
  interpreterResult: ReturnType<typeof spawnSync> | null,
  diffResult: ReturnType<typeof spawnSync> | null
): TestCaseReport {
  return new TestCaseReport(
    test_result,
    parserResult?.status ?? null,
    interpreterResult?.status ?? null,
    (parserResult?.stdout ?? null) as string | null,
    (parserResult?.stderr ?? null) as string | null,
    (interpreterResult?.stdout ?? null) as string | null,
    (interpreterResult?.stderr ?? null) as string | null,
    (diffResult?.stdout ?? null) as string | null
  );
}

function writeReport(
  test: Test,
  reports: Record<string, MutableCategoryReport>,
  parserResult: ReturnType<typeof spawnSync> | null,
  interpreterResult: ReturnType<typeof spawnSync> | null,
  diffResult: ReturnType<typeof spawnSync> | null
) {
  if (!(test.def.category in reports)) {
    reports[test.def.category] = {
      total_points: 0,
      passed_points: 0,
      test_results: {},
    };
  }

  const categoryReport = reports[test.def.category];
  if (!categoryReport) {
    throw new Error("Category report should have been initialized at this point");
  }

  const result: TestResult = getTestResult(test, parserResult, interpreterResult, diffResult);

  categoryReport.total_points += test.def.points;
  if (result === TestResult.PASSED) {
    categoryReport.passed_points += test.def.points;
  }

  categoryReport.test_results[test.def.name] = getTestReport(
    result,
    parserResult,
    interpreterResult,
    diffResult
  );
}

function main(): void {
  /**
   * The main entry point for the SOL26 integration testing script.
   * It parses command-line arguments and executes the testing process.
   */

  // Set up logging
  // IPP: You do not have to use logging - but it is the recommended practice.
  //      See https://getpino.io/#/docs/api for more information.
  logger.level = "warn";

  // Parse the CLI arguments
  const args = parseArguments();

  // Enable debug or info logging if the verbose flag was set twice or once
  if (args.verbose >= 2) {
    logger.level = "debug";
  } else if (args.verbose === 1) {
    logger.level = "info";
  }

  const tests = discoverTests(args);

  const discovered_test_cases = tests.map((test) => test.def);

  const unexecuted: Record<string, UnexecutedReason> = {};
  const results: Record<string, CategoryReport> = {};

  for (const filtered_out_test of tests.filter((test) => !filterTest(test, args))) {
    unexecuted[filtered_out_test.def.name] = new UnexecutedReason(
      UnexecutedReasonCode.FILTERED_OUT
    );
  }

  for (const test of tests.filter((test) => filterTest(test, args))) {
    executeTest(test, results, unexecuted);
  }

  let passed = true;
  for (const [category, categoryReport] of Object.entries(results)) {
    for (const [test, testResult] of Object.entries(categoryReport.test_results)) {
      if (testResult.result !== TestResult.PASSED) {
        logger.info(`${category}/${test} FAILED: ${testResult.result}`);
        passed = false;
      }
    }
  }

  if (passed) logger.info("All tests passed!");

  const report = new TestReport({
    discovered_test_cases,
    unexecuted,
    results,
  });
  writeResult(report, args.output);
}

main();
