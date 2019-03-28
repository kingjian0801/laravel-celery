<h1 align="center"> laravel-celery </h1>

<p align="center"> a laravel celery dependency package.</p>

## 前言
参考官方包[gjedeer/celery-php](https://github.com/gjedeer/celery-php)，为个人项目做了简化，仅支持以redis为中间人发布celery任务。

## 安装

```shell
$ composer require kingjian0801/laravel-celery:dev-master
```

## 基本使用

```shell
use Kingjian0801\LaravelCelery\Celery;
```
1.PostTask(发布任务)

```shell
$celery = new Celery('redis地址', '密码', '数据库','队列名称');
$celery->PostTask('任务名称', 任务数据(数组));
```
2.getAsyncResultMessage(查询任务状态)

```shell
$celery = new Celery('redis地址', '密码', '数据库','队列名称');
$celery->getAsyncResultMessage('任务名称','任务key值');
```