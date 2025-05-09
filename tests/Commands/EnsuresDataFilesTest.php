<?php

namespace Io238\ISOCountries\Tests\Commands;

use Illuminate\Support\Facades\File;
use Io238\ISOCountries\Commands\EnsuresDataFiles;
use Mockery;

beforeEach(function () {
    // 确保每个测试开始时 Mockery 是干净的
    Mockery::close();
});

afterEach(function () {
    // 清理模拟
    Mockery::close();
});

it('creates destination directories when they dont exist', function () {
    // 创建测试类实例
    $testCommand = new class {
        use EnsuresDataFiles;

        // 提供必要的方法以便trait能够正常工作
        public function info($message) {}

        public function warn($message) {}

        // 公开方法以便测试
        public function runEnsureDataFilesExist()
        {
            $this->ensureDataFilesExist();
        }
    };

    // 模拟File门面方法
    File::shouldReceive('isDirectory')
        ->andReturn(true)
        ->byDefault();

    File::shouldReceive('ensureDirectoryExists')
        ->atLeast()->once() // 不指定具体次数，而是确保至少被调用一次
        ->andReturnTrue();

    File::shouldReceive('allFiles')
        ->andReturn([])
        ->byDefault();

    // 调用被测方法
    $testCommand->runEnsureDataFilesExist();

    // 断言成功完成
    expect(true)->toBeTrue(); // 如果运行到这里，说明没有异常
});

it('copies only changed files', function () {
    // 创建测试类实例
    $testCommand = new class {
        use EnsuresDataFiles;

        // 存储记录的消息
        public $messages = [];

        public function info($message)
        {
            $this->messages[] = $message;
        }

        public function warn($message)
        {
            $this->messages[] = "WARNING: {$message}";
        }

        // 公开方法以便测试
        public function runEnsureDirectoryCopy($source, $destination, $label, $filter = null)
        {
            $this->ensureDirectoryCopy($source, $destination, $label, $filter);
        }
    };

    // 创建模拟文件对象
    $mockFile1 = Mockery::mock();
    $mockFile1->shouldReceive('getExtension')->andReturn('php');
    $mockFile1->shouldReceive('getRelativePathname')->andReturn('test1.php');
    $mockFile1->shouldReceive('getRealPath')->andReturn('/path/to/test1.php');

    $mockFile2 = Mockery::mock();
    $mockFile2->shouldReceive('getExtension')->andReturn('php');
    $mockFile2->shouldReceive('getRelativePathname')->andReturn('test2.php');
    $mockFile2->shouldReceive('getRealPath')->andReturn('/path/to/test2.php');

    // 设置模拟
    File::shouldReceive('isDirectory')->with('/source')->andReturn(true);
    File::shouldReceive('ensureDirectoryExists')->andReturnTrue();
    File::shouldReceive('allFiles')->andReturn([$mockFile1, $mockFile2]);

    // 文件1不存在，应该被复制
    File::shouldReceive('exists')->with('/dest/test1.php')->andReturn(false);
    File::shouldReceive('copy')->with('/path/to/test1.php', '/dest/test1.php')->once();

    // 文件2存在且未修改，不应该被复制
    File::shouldReceive('exists')->with('/dest/test2.php')->andReturn(true);
    File::shouldReceive('lastModified')->with($mockFile2)->andReturn(100);
    File::shouldReceive('lastModified')->with('/dest/test2.php')->andReturn(100);

    // 调用被测方法
    $testCommand->runEnsureDirectoryCopy('/source', '/dest', 'Test', 'php');

    // 断言
    $messages = implode(', ', $testCommand->messages);
    expect($messages)->toContain('Copied Test file: test1.php');
    expect($messages)->not->toContain('Copied Test file: test2.php');
});

it('warns when source directory doesnt exist', function () {
    // 创建测试类实例
    $testCommand = new class {
        use EnsuresDataFiles;

        // 存储记录的消息
        public $messages = [];

        public function info($message)
        {
            $this->messages[] = $message;
        }

        public function warn($message)
        {
            $this->messages[] = "WARNING: {$message}";
        }

        // 公开方法以便测试
        public function runEnsureDirectoryCopy($source, $destination, $label, $filter = null)
        {
            $this->ensureDirectoryCopy($source, $destination, $label, $filter);
        }
    };

    // 设置模拟
    File::shouldReceive('isDirectory')->with('/nonexistent')->andReturn(false);

    // 调用被测方法
    $testCommand->runEnsureDirectoryCopy('/nonexistent', '/dest', 'Nonexistent');

    // 断言
    $messages = implode(', ', $testCommand->messages);
    expect($messages)->toContain('WARNING: Vendor Nonexistent directory not found');
});

it('filters files by extension', function () {
    // 创建测试类实例
    $testCommand = new class {
        use EnsuresDataFiles;

        // 存储记录的消息
        public $messages = [];

        public function info($message)
        {
            $this->messages[] = $message;
        }

        public function warn($message)
        {
            $this->messages[] = "WARNING: {$message}";
        }

        // 公开方法以便测试
        public function runEnsureDirectoryCopy($source, $destination, $label, $filter = null)
        {
            $this->ensureDirectoryCopy($source, $destination, $label, $filter);
        }
    };

    // 创建模拟文件对象
    $phpFile = Mockery::mock();
    $phpFile->shouldReceive('getExtension')->andReturn('php');
    $phpFile->shouldReceive('getRelativePathname')->andReturn('test.php');
    $phpFile->shouldReceive('getRealPath')->andReturn('/path/to/test.php');

    $jsonFile = Mockery::mock();
    $jsonFile->shouldReceive('getExtension')->andReturn('json');
    $jsonFile->shouldReceive('getRelativePathname')->andReturn('test.json');

    // 设置模拟
    File::shouldReceive('isDirectory')->with('/source')->andReturn(true);
    File::shouldReceive('ensureDirectoryExists')->andReturnTrue();
    File::shouldReceive('allFiles')->andReturn([$phpFile, $jsonFile]);

    // PHP文件应该被检查
    File::shouldReceive('exists')->with('/dest/test.php')->andReturn(false);
    File::shouldReceive('copy')->with('/path/to/test.php', '/dest/test.php')->once();

    // JSON文件不应该被处理

    // 调用被测方法 - 只复制PHP文件
    $testCommand->runEnsureDirectoryCopy('/source', '/dest', 'Test', 'php');

    // 断言
    $messages = implode(', ', $testCommand->messages);
    expect($messages)->toContain('Copied Test file: test.php');
    expect($messages)->not->toContain('test.json');
});
