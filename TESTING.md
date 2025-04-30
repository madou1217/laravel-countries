# Testing Guide / 测试指南

- [English](#english)
- [中文](#中文)

<a id="english"></a>
## English

This document provides guidance on how to test this package in different Laravel version environments.

### Automated Testing

This project uses GitHub Actions to set up automated testing workflows, which can test the package's compatibility across multiple PHP and Laravel version combinations. The tests run automatically on each push and pull request.

To view test results:
1. Go to the GitHub repository page
2. Click on the "Actions" tab
3. Check the latest test workflow execution results in the list

### Testing with Different Laravel Versions Locally

If you want to test the package's compatibility with different Laravel versions locally, you can use the following methods:

#### Method 1: Using Docker

##### Prerequisites:
- Docker installed
- Docker Compose installed

##### Steps:

1. Create Docker containers for each Laravel version:

```bash
# For example, create a testing environment for Laravel 11
docker run --rm -v $(pwd):/app -w /app php:8.1-cli composer create-project laravel/laravel:^11.0 test-laravel11

# Enter the test directory
cd test-laravel11

# Add the package as a local dependency
# First create a composer.json containing the local package path
cat <<EOF >> composer.json
{
    "repositories": [
        {
            "type": "path",
            "url": "../"
        }
    ]
}
EOF

# Install the package
docker run --rm -v $(pwd):/app -w /app php:8.1-cli composer require io238/laravel-iso-countries:@dev

# Build ISO data with artisan command
docker run --rm -v $(pwd):/app -w /app php:8.1-cli php artisan countries:build

# Test functionality
# ...you can write test scripts here
```

#### Method 2: Using Multiple Laravel Projects

##### Steps:

1. Create a new project for each Laravel version you need to test:

```bash
# Laravel 7
composer create-project --prefer-dist laravel/laravel:^7.0 test-laravel7

# Laravel 8 
composer create-project --prefer-dist laravel/laravel:^8.0 test-laravel8 

# Laravel 9
composer create-project --prefer-dist laravel/laravel:^9.0 test-laravel9

# Laravel 10
composer create-project --prefer-dist laravel/laravel:^10.0 test-laravel10

# Laravel 11
composer create-project --prefer-dist laravel/laravel:^11.0 test-laravel11

# Laravel 12 (requires PHP 8.2+)
composer create-project --prefer-dist laravel/laravel:^12.0 test-laravel12
```

2. Add the local package dependency in each project:

```bash
cd test-laravel11

# Configure local repository path
composer config repositories.local path "../path/to/laravel-iso-countries"

# Install the package
composer require io238/laravel-iso-countries:@dev

# Publish configuration
php artisan vendor:publish --provider="Io238\ISOCountries\ISOCountriesServiceProvider" --tag="config"

# Build ISO data
php artisan countries:build

# Create test routes
# Add test routes in routes/web.php to check functionality
```

3. Create test routes (`routes/web.php`):

```php
use Illuminate\Support\Facades\Route;
use Io238\ISOCountries\Models\Country;
use Io238\ISOCountries\Models\Language;
use Io238\ISOCountries\Models\Currency;

// Test ISO countries route
Route::get('/countries', function () {
    return [
        'total_countries' => Country::count(),
        'sample_country' => Country::find('CN'),
        'languages_in_china' => Country::find('CN')->languages,
        'currencies_in_china' => Country::find('CN')->currencies
    ];
});
```

4. Start the server:

```bash
php artisan serve
```

5. Test the API:

```bash
curl http://127.0.0.1:8000/countries
```

### Version Compatibility Notes

- **Laravel 7, 8**: Requires PHP 7.3+ to 8.1
- **Laravel 9**: Requires PHP 8.0+
- **Laravel 10**: Requires PHP 8.1+
- **Laravel 11, 12**: Requires PHP 8.2+

### Potential Issues and Solutions

Issues you may encounter when testing across different Laravel versions:

#### 1. Dependency Conflicts

**Issue**: Some Laravel versions may conflict with specific dependency package versions.
**Solution**: Check the version constraints in `composer.json` to ensure they're flexible enough to accommodate different Laravel versions.

#### 2. API Changes

**Issue**: Laravel's API may change between different versions.
**Solution**: For critical API calls, use conditional checks or ensure that methods used are available in all supported versions.

---

<a id="中文"></a>
## 中文

本文档提供了如何在不同 Laravel 版本环境中测试此包的指南。

### 自动化测试

本项目使用 GitHub Actions 设置了自动化测试流程，可以在多个 PHP 和 Laravel 版本组合中测试包的兼容性。每次推送和 Pull Request 时，测试会自动运行。

查看测试结果：
1. 进入 GitHub 仓库页面
2. 点击 "Actions" 标签
3. 在列表中查看最新的测试工作流执行结果

### 本地测试不同 Laravel 版本

如果你想在本地测试此包与不同 Laravel 版本的兼容性，可以使用以下几种方法：

#### 方法一：使用 Docker

##### 前提条件:
- 安装 Docker
- 安装 Docker Compose

##### 步骤:

1. 为每个 Laravel 版本创建 Docker 容器：

```bash
# 例如，为 Laravel 11 创建测试环境
docker run --rm -v $(pwd):/app -w /app php:8.1-cli composer create-project laravel/laravel:^11.0 test-laravel11

# 进入测试目录
cd test-laravel11

# 将包作为本地依赖添加
# 首先创建一个包含本地包路径的 composer.json
cat <<EOF >> composer.json
{
    "repositories": [
        {
            "type": "path",
            "url": "../"
        }
    ]
}
EOF

# 安装包
docker run --rm -v $(pwd):/app -w /app php:8.1-cli composer require io238/laravel-iso-countries:@dev

# 运行 artisan 命令构建 ISO 数据
docker run --rm -v $(pwd):/app -w /app php:8.1-cli php artisan countries:build

# 测试功能
# ...可以在这里编写测试脚本
```

#### 方法二：使用多个 Laravel 项目

##### 步骤:

1. 为每个需要测试的 Laravel 版本创建一个新项目：

```bash
# Laravel 7
composer create-project --prefer-dist laravel/laravel:^7.0 test-laravel7

# Laravel 8 
composer create-project --prefer-dist laravel/laravel:^8.0 test-laravel8 

# Laravel 9
composer create-project --prefer-dist laravel/laravel:^9.0 test-laravel9

# Laravel 10
composer create-project --prefer-dist laravel/laravel:^10.0 test-laravel10

# Laravel 11
composer create-project --prefer-dist laravel/laravel:^11.0 test-laravel11

# Laravel 12 (需要 PHP 8.2+)
composer create-project --prefer-dist laravel/laravel:^12.0 test-laravel12
```

2. 在每个项目中添加本地包依赖：

```bash
cd test-laravel11

# 配置本地仓库路径
composer config repositories.local path "../path/to/laravel-iso-countries"

# 安装包
composer require io238/laravel-iso-countries:@dev

# 发布配置
php artisan vendor:publish --provider="Io238\ISOCountries\ISOCountriesServiceProvider" --tag="config"

# 构建ISO数据
php artisan countries:build

# 创建测试路由
# 在 routes/web.php 中添加测试路由来检查功能
```

3. 创建测试路由 (`routes/web.php`):

```php
use Illuminate\Support\Facades\Route;
use Io238\ISOCountries\Models\Country;
use Io238\ISOCountries\Models\Language;
use Io238\ISOCountries\Models\Currency;

// 测试ISO国家路由
Route::get('/countries', function () {
    return [
        'total_countries' => Country::count(),
        'sample_country' => Country::find('CN'),
        'languages_in_china' => Country::find('CN')->languages,
        'currencies_in_china' => Country::find('CN')->currencies
    ];
});
```

4. 启动服务器:

```bash
php artisan serve
```

5. 测试 API:

```bash
curl http://127.0.0.1:8000/countries
```

### 特定版本的兼容性说明

- **Laravel 7, 8**: 要求 PHP 7.3+ 到 8.1
- **Laravel 9**: 要求 PHP 8.0+
- **Laravel 10**: 要求 PHP 8.1+
- **Laravel 11, 12**: 要求 PHP 8.2+

### 潜在问题及解决方案

在不同 Laravel 版本测试中可能遇到的问题：

#### 1. 依赖冲突

**问题**: 某些 Laravel 版本可能与特定的依赖包版本冲突。  
**解决方案**: 检查 `composer.json` 中的版本约束，确保它们足够灵活以适应不同版本的 Laravel。

#### 2. API 变更

**问题**: Laravel 的 API 在不同版本之间可能有变化。  
**解决方案**: 对于关键的 API 调用，使用条件检查或确保调用的方法在所有支持的版本中都可用。 
