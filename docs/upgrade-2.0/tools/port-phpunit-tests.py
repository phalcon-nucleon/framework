#!/usr/bin/env python3
"""Mechanical port of PHPUnit 5 test files to PHPUnit 11.

Usage: python3 docs/upgrade-2.0/tools/port-phpunit-tests.py tests/Test/Cli [more files or directories]

Handles: strict_types, `: void` on fixtures and tests, static data providers, @dataProvider / @depends /
@expectedException* annotations, setExpectedException(), setExpectedExceptionRegExp(), will(returnValue()),
assertInternalType(). The result must be reviewed: providers that use $this, assertContains() on strings,
mocks of final methods, etc. are left as they are.
"""

import os
import re
import sys

FIXTURES = {
    'setUp': 'protected function setUp(): void',
    'tearDown': 'protected function tearDown(): void',
    'setUpBeforeClass': 'public static function setUpBeforeClass(): void',
    'tearDownAfterClass': 'public static function tearDownAfterClass(): void',
}

INTERNAL_TYPES = {
    'array': 'assertIsArray', 'string': 'assertIsString', 'int': 'assertIsInt', 'integer': 'assertIsInt',
    'bool': 'assertIsBool', 'boolean': 'assertIsBool', 'float': 'assertIsFloat', 'callable': 'assertIsCallable',
    'object': 'assertIsObject', 'resource': 'assertIsResource', 'numeric': 'assertIsNumeric', 'scalar': 'assertIsScalar',
}


def port(source: str) -> str:
    s = source

    if 'declare(strict_types=1)' not in s:
        s = re.sub(r'^<\?php\s*\n', '<?php\n\ndeclare(strict_types=1);\n\n', s, count=1)

    for name, signature in FIXTURES.items():
        s = re.sub(r'(public|protected)\s+(static\s+)?function ' + name + r'\(\)\s*(?::\s*void)?', signature, s)

    providers = set(re.findall(r'@dataProvider\s+(\w+)', s))
    for provider in providers:
        s = re.sub(r'public\s+function ' + provider + r'\(\)(\s*:\s*\w+)?', 'public static function ' + provider + '(): array', s)

    # Docblock annotations -> attributes / statements.
    def method_with_doc(match: re.Match) -> str:
        doc, indent, signature, name, params, body_open = match.groups()
        attributes, statements = [], []

        for provider in re.findall(r'@dataProvider\s+(\w+)', doc):
            attributes.append(f"#[\\PHPUnit\\Framework\\Attributes\\DataProvider('{provider}')]")
        for depends in re.findall(r'@depends\s+(\w+)', doc):
            attributes.append(f"#[\\PHPUnit\\Framework\\Attributes\\Depends('{depends}')]")

        exception = re.search(r'@expectedException\s+\\?([\w\\]+)', doc)
        if exception:
            cls = exception.group(1).replace('PHPUnit_Framework_ExpectationFailedException', 'PHPUnit\\Framework\\ExpectationFailedException')
            statements.append(f"$this->expectException(\\{cls}::class);")
        message = re.search(r'@expectedExceptionMessage\s+(.+)', doc)
        if message:
            statements.append(f"$this->expectExceptionMessage({php_string(message.group(1).strip())});")
        regexp = re.search(r'@expectedExceptionMessageRegExp\s+(.+)', doc)
        if regexp:
            statements.append(f"$this->expectExceptionMessageMatches({php_string(regexp.group(1).strip())});")

        doc = re.sub(r'\n\s*\*\s*@(dataProvider|depends|expectedException\w*)\b[^\n]*', '', doc)
        if re.fullmatch(r'/\*\*(\s*\*)*\s*\*/', doc.strip()):
            doc = ''

        out = (doc + '\n' + indent if doc else '')
        out += ''.join(a + '\n' + indent for a in attributes)
        out += signature + name + '(' + params + ')' + body_open
        if statements:
            out += ''.join('\n' + indent + '    ' + st for st in statements)
        return out

    s = re.sub(
        r'(/\*\*(?:(?!\*/).)*?\*/)\n([ \t]*)((?:public|protected)\s+function\s+)(\w+)\(([^)]*)\)((?:\s*:\s*\??\w+)?\s*\{)',
        method_with_doc, s, flags=re.S)

    # Test methods return void.
    s = re.sub(r'(public function test\w+\([^)]*\))(\s*\{)', r'\1: void\2', s)

    s = re.sub(r'\$this->setExpectedException(?:RegExp)?\(\s*([^,;]+?)\s*\);',
               lambda m: f"$this->expectException({class_ref(m.group(1))});", s)
    s = re.sub(r'\$this->setExpectedExceptionRegExp\(\s*([^,]+?)\s*,\s*([^;]+?)\s*\);',
               lambda m: f"$this->expectException({class_ref(m.group(1))});\n        $this->expectExceptionMessageMatches({m.group(2)});", s)
    s = re.sub(r'\$this->setExpectedException\(\s*([^,]+?)\s*,\s*([^;]+?)\s*\);',
               lambda m: f"$this->expectException({class_ref(m.group(1))});\n        $this->expectExceptionMessage({m.group(2)});", s)

    s = re.sub(r'->will\(\$this->returnValue\((.*?)\)\);', r'->willReturn(\1);', s)
    s = re.sub(r"\$this->assertInternalType\('(\w+)',\s*", lambda m: f"$this->{INTERNAL_TYPES.get(m.group(1), 'assertIs' + m.group(1).capitalize())}(", s)
    s = s.replace('\\PHPUnit_Framework_TestCase', '\\PHPUnit\\Framework\\TestCase').replace('PHPUnit_Framework_TestCase', 'PHPUnit\\Framework\\TestCase')

    return s


def class_ref(expression: str) -> str:
    expression = expression.strip()
    if expression.startswith(("'", '"')):
        return '\\' + expression.strip('\'"').lstrip('\\') + '::class'
    return expression


def php_string(value: str) -> str:
    return "'" + value.replace('\\', '\\\\').replace("'", "\\'") + "'"


def files(paths):
    for path in paths:
        if os.path.isdir(path):
            for root, _, names in os.walk(path):
                for name in names:
                    if name.endswith('.php'):
                        yield os.path.join(root, name)
        else:
            yield path


if __name__ == '__main__':
    for file in files(sys.argv[1:]):
        with open(file) as f:
            source = f.read()
        ported = port(source)
        if ported != source:
            with open(file, 'w') as f:
                f.write(ported)
            print('ported', file)
