<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Tests\Contract;

use PhpNoobs\PhpRename\Application\PhpRename;
use PhpNoobs\PhpRename\Application\PhpRenameTransaction;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenamePlan;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenameResult;
use PhpNoobs\PhpRename\Domain\Rename\Step\RenameStepContext;
use PhpNoobs\PhpRename\Domain\Rename\Step\RenameStepResult;
use PhpNoobs\PhpRename\Domain\Rename\Transaction\RenameTransactionResult;
use PhpNoobs\PhpRename\Domain\Rename\Transaction\RenameTransactionStatus;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;
use PHPUnit\Framework\TestCase;

/**
 * Locks the public API surface consumed by direct users and future orchestrators.
 */
final class PhpRenamePublicApiContractTest extends TestCase
{
    /**
     * Ensures the facade public method names remain stable.
     */
    public function testItExposesTheExpectedFacadeMethods(): void
    {
        self::assertSame([
            'beginTransaction',
            'executeStep',
            'executeStepArrowFunctionLocalVariableRenameInFile',
            'executeStepArrowFunctionLocalVariableRenameInFunction',
            'executeStepArrowFunctionLocalVariableRenameInMethod',
            'executeStepArrowFunctionParameterRenameInFile',
            'executeStepArrowFunctionParameterRenameInFunction',
            'executeStepArrowFunctionParameterRenameInMethod',
            'executeStepClassConstantRename',
            'executeStepClassFqcnRename',
            'executeStepClassRename',
            'executeStepClosureLocalVariableRenameInFile',
            'executeStepClosureLocalVariableRenameInFunction',
            'executeStepClosureLocalVariableRenameInMethod',
            'executeStepClosureParameterRenameInFile',
            'executeStepClosureParameterRenameInFunction',
            'executeStepClosureParameterRenameInMethod',
            'executeStepConstantFqcnRename',
            'executeStepConstantRename',
            'executeStepEnumCaseRename',
            'executeStepFunctionFqcnRename',
            'executeStepFunctionParameterRename',
            'executeStepFunctionRename',
            'executeStepMethodParameterRename',
            'executeStepMethodRename',
            'executeStepNestedCallableLocalVariableRename',
            'executeStepNestedCallableParameterRename',
            'executeStepPropertyRename',
            'fromBuild',
            'fromDirectory',
            'planArrowFunctionLocalVariableRenameInFile',
            'planArrowFunctionLocalVariableRenameInFunction',
            'planArrowFunctionLocalVariableRenameInMethod',
            'planArrowFunctionParameterRenameInFile',
            'planArrowFunctionParameterRenameInFunction',
            'planArrowFunctionParameterRenameInMethod',
            'planClassConstantRename',
            'planClassFqcnRename',
            'planClassRename',
            'planClosureLocalVariableRenameInFile',
            'planClosureLocalVariableRenameInFunction',
            'planClosureLocalVariableRenameInMethod',
            'planClosureParameterRenameInFile',
            'planClosureParameterRenameInFunction',
            'planClosureParameterRenameInMethod',
            'planConstantFqcnRename',
            'planConstantRename',
            'planEnumCaseRename',
            'planFunctionFqcnRename',
            'planFunctionParameterRename',
            'planFunctionRename',
            'planMethodParameterRename',
            'planMethodRename',
            'planNestedCallableLocalVariableRename',
            'planNestedCallableParameterRename',
            'planPropertyRename',
            'renameArrowFunctionLocalVariableInFile',
            'renameArrowFunctionLocalVariableInFunction',
            'renameArrowFunctionLocalVariableInMethod',
            'renameArrowFunctionParameterInFile',
            'renameArrowFunctionParameterInFunction',
            'renameArrowFunctionParameterInMethod',
            'renameClass',
            'renameClassConstant',
            'renameClassFqcn',
            'renameClosureLocalVariableInFile',
            'renameClosureLocalVariableInFunction',
            'renameClosureLocalVariableInMethod',
            'renameClosureParameterInFile',
            'renameClosureParameterInFunction',
            'renameClosureParameterInMethod',
            'renameConstant',
            'renameConstantFqcn',
            'renameEnumCase',
            'renameFunction',
            'renameFunctionFqcn',
            'renameFunctionParameter',
            'renameMethod',
            'renameMethodParameter',
            'renameNestedCallableLocalVariable',
            'renameNestedCallableParameter',
            'renameProperty',
        ], $this->publicMethodNames(PhpRename::class));
    }

    /**
     * Ensures the transaction public method names remain stable.
     */
    public function testItExposesTheExpectedTransactionMethods(): void
    {
        self::assertSame([
            'commit',
            'commitAndSave',
            'commitAndSaveSourceFile',
            'renameArrowFunctionLocalVariableInFile',
            'renameArrowFunctionLocalVariableInFunction',
            'renameArrowFunctionLocalVariableInMethod',
            'renameArrowFunctionParameterInFile',
            'renameArrowFunctionParameterInFunction',
            'renameArrowFunctionParameterInMethod',
            'renameClass',
            'renameClassConstant',
            'renameClassFqcn',
            'renameClosureLocalVariableInFile',
            'renameClosureLocalVariableInFunction',
            'renameClosureLocalVariableInMethod',
            'renameClosureParameterInFile',
            'renameClosureParameterInFunction',
            'renameClosureParameterInMethod',
            'renameConstant',
            'renameConstantFqcn',
            'renameEnumCase',
            'renameFunction',
            'renameFunctionFqcn',
            'renameFunctionParameter',
            'renameMethod',
            'renameMethodParameter',
            'renameNestedCallableLocalVariable',
            'renameNestedCallableParameter',
            'renameProperty',
            'rollback',
            'status',
        ], $this->publicMethodNames(PhpRenameTransaction::class));
    }

    /**
     * Ensures facade method return types remain stable.
     */
    public function testItExposesTheExpectedFacadeReturnTypes(): void
    {
        foreach ($this->publicMethodNames(PhpRename::class) as $methodName) {
            $returnType = new \ReflectionMethod(PhpRename::class, $methodName)->getReturnType();

            self::assertInstanceOf(\ReflectionNamedType::class, $returnType);

            if (str_starts_with($methodName, 'plan')) {
                self::assertSame(RenamePlan::class, $returnType->getName(), $methodName);

                continue;
            }

            if (str_starts_with($methodName, 'rename')) {
                self::assertSame(RenameResult::class, $returnType->getName(), $methodName);

                continue;
            }

            if (str_starts_with($methodName, 'executeStep')) {
                self::assertSame(RenameStepResult::class, $returnType->getName(), $methodName);
            }
        }

        self::assertSame(PhpRenameTransaction::class, $this->returnTypeName(PhpRename::class, 'beginTransaction'));
        self::assertSame('self', $this->returnTypeName(PhpRename::class, 'fromBuild'));
        self::assertSame('self', $this->returnTypeName(PhpRename::class, 'fromDirectory'));
    }

    /**
     * Ensures transaction method return types remain stable.
     */
    public function testItExposesTheExpectedTransactionReturnTypes(): void
    {
        foreach ($this->publicMethodNames(PhpRenameTransaction::class) as $methodName) {
            if (str_starts_with($methodName, 'rename')) {
                self::assertSame(RenameResult::class, $this->returnTypeName(PhpRenameTransaction::class, $methodName), $methodName);

                continue;
            }

            if ('status' === $methodName) {
                self::assertSame(RenameTransactionStatus::class, $this->returnTypeName(PhpRenameTransaction::class, $methodName), $methodName);

                continue;
            }

            self::assertSame(RenameTransactionResult::class, $this->returnTypeName(PhpRenameTransaction::class, $methodName), $methodName);
        }
    }

    /**
     * Ensures step context remains an immutable orchestration DTO.
     */
    public function testItExposesStableRenameStepContextProperties(): void
    {
        $reflection = new \ReflectionClass(RenameStepContext::class);

        self::assertTrue($reflection->isReadOnly());
        self::assertSame([
            'baseBuild',
            'currentBuild',
            'overlay',
        ], $this->publicPropertyNames(RenameStepContext::class));
    }

    /**
     * Ensures step result remains an immutable orchestration DTO.
     */
    public function testItExposesStableRenameStepResultProperties(): void
    {
        $reflection = new \ReflectionClass(RenameStepResult::class);

        self::assertTrue($reflection->isReadOnly());
        self::assertSame([
            'applied',
            'context',
            'diagnostics',
            'plan',
            'renameResult',
            'touchedFiles',
        ], $this->publicPropertyNames(RenameStepResult::class));

        self::assertSame(RenameStepContext::class, $this->propertyTypeName(RenameStepResult::class, 'context'));
        self::assertSame(RenamePlan::class, $this->propertyTypeName(RenameStepResult::class, 'plan'));
        self::assertSame(RenameResult::class, $this->propertyTypeName(RenameStepResult::class, 'renameResult'));
        self::assertSame(RenameDiagnosticCollection::class, $this->propertyTypeName(RenameStepResult::class, 'diagnostics'));
        self::assertSame(VirtualPhpSourceFileCollection::class, $this->propertyTypeName(RenameStepResult::class, 'touchedFiles'));
        self::assertSame('bool', $this->propertyTypeName(RenameStepResult::class, 'applied'));
    }

    /**
     * Returns public methods declared directly on a class.
     *
     * @param class-string $className the class name to inspect
     *
     * @return list<string>
     */
    private function publicMethodNames(string $className): array
    {
        $reflection = new \ReflectionClass($className);
        $methodNames = [];

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $className) {
                continue;
            }

            if ($method->isConstructor()) {
                continue;
            }

            $methodNames[] = $method->getName();
        }

        sort($methodNames);

        return $methodNames;
    }

    /**
     * Returns public properties declared on a class.
     *
     * @param class-string $className the class name to inspect
     *
     * @return list<string>
     */
    private function publicPropertyNames(string $className): array
    {
        $reflection = new \ReflectionClass($className);
        $propertyNames = [];

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $propertyNames[] = $property->getName();
        }

        sort($propertyNames);

        return $propertyNames;
    }

    /**
     * Returns the named return type for one method.
     *
     * @param class-string $className  the class name to inspect
     * @param string       $methodName the method name to inspect
     */
    private function returnTypeName(string $className, string $methodName): string
    {
        $returnType = new \ReflectionMethod($className, $methodName)->getReturnType();

        self::assertInstanceOf(\ReflectionNamedType::class, $returnType);

        return $returnType->getName();
    }

    /**
     * Returns the named type for one property.
     *
     * @param class-string $className    the class name to inspect
     * @param string       $propertyName the property name to inspect
     */
    private function propertyTypeName(string $className, string $propertyName): string
    {
        $propertyType = new \ReflectionProperty($className, $propertyName)->getType();

        self::assertInstanceOf(\ReflectionNamedType::class, $propertyType);

        return $propertyType->getName();
    }
}
