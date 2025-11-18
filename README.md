<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Kaledo Loyalty API

### Rodando o projeto localmente

Clone o projeto

Depois de clonar o projeto, entre na pasta do projeto e execute o comando abaixo para instalar as dependências do projeto.

**OBS:** Verificar a versão do PHP desejada antes de rodar o comando abaixo

```sh
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php83-composer:latest \
    composer install --ignore-platform-reqs
```

Após rodar o comando acima, será necessário fazer mais uma "build" para garantir que o ambiente com swoole esteja rodando de forma adequada.

`sail build --no-cache`

Após essa build, a extensão e configurações necessárias para rodar o Swoole estarão concluídas. Agora, você precisará instalar o node para manter um "watcher" configurado para refletir automaticamente as mudanças realizadas no seu código.

`npm install --save-dev chokidar`

Depois de instalar as dependências, copie o arquivo _**.env.example**_ para _**.env**_ e execute o comando abaixo para gerar a chave do projeto.

```sh
sail artisan key:generate
```

Após isso, teremos que instalar as chaves do Passport (link)

```sh
sail artisan passport:keys --force
```
