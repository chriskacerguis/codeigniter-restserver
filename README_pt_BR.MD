# CodeIgniter Rest Server

Implementação completa de um servidor RESTful para CodeIgniter usando uma biblioteca, um arquivo config e um controller.

## Requisitos

1. PHP 5.4+
2. CodeIgniter 3.0+

_Nota: para suporte à versão 1.7.x baixe a v2.2 da aba de Downloads_

## Atualização importante na vs. 4.0.0

Note que a versão 4.0.0 está em andamento, e é considerada uma versão "breaking change".  Como o CI 3.1.0 agora tem suporte nativo ao Composer, esta biblioteca está sendo criada baseada no composer.

Dê uma olhada no branch "development" e veja o que está acontecendo.

## Instalação

Arraste os arquivos **application/libraries/Format.php** e **application/libraries/REST_Controller.php** para dentro do diretório da sua aplicação. Para usar `require_once` no topo dos seus controllers pra carregá-los dentro do escopo. Adicionalmente, copie o arquivo **rest.php** de **application/config** para o diretório de configuração de sua aplicação.

## Tratando Requisições

Quando seu controller extende de `REST_Controller`, os nomes dos métodos vão ser sufixados com o método HTTP usado para acessar a requisição. Se você está fazendo uma chamada HTTP `GET` para `/books`, por exemplo, será chamado o método `Books#index_get()`.

Isso permite a você implementar uma interface RESTful facilmente:

```php
class Books extends REST_Controller
{
  public function index_get()
  {
    // Display all books
  }

  public function index_post()
  {
    // Create a new book
  }
}
```

`REST_Controller` também suporta os métodos `PUT` e `DELETE`, permitindo a você implementar uma real interface RESTful.


Acessar parâmetros também é fácil. Apenas use o nome do método HTTP como um método:

```php
$this->get('blah'); // parâmetro GET
$this->post('blah'); // parâmetro POST
$this->put('blah'); // parâmetro PUT
```

A especificação HTTP para requisições do tipo DELETE impede o uso de parâmetros.  Para estas requisições, você pode adicionar itens à URL:

```php
public function index_delete($id)
{
	$this->response([
		'returned from delete:' => $id,
	]);
}
```

Se parâmetros de consulta (query strings) são passados através da URL, independentemente de ser de uma requisição GET, podem ser obtidos através do método `query`:

```php
$this->query('blah'); // Query param
```

## Content Types

`REST_Controller` suporta vários tipos de formato request/response, incluindo XML, JSON and PHP serializado. Por padrão, a classe vai checar a URL e procurar por um formato seja como uma extensão ou um segmento separado.

Isso significa que suas URLs podem ser assim:
```
http://example.com/books.json
http://example.com/books?format=json
```

Isso pode ser ruim de trabalhar junto com segmentos URI, então, a recomendado usar um cabeçalho HTTP `Accept`:

```bash
$ curl -H "Accept: application/json" http://example.com
```

Qualquer resposta (response) que você faça na classe (veja [responses](#responses) para mais detalhes) serão serializadas no formato solicitado.

## Respostas (responses)

A classe provê um método `response()` que permite retornar os dados no formato solicitado pelo usuário.

Retornar um objeto / array / string / etc é simples:

```php
public function index_get()
{
  $this->response($this->db->get('books')->result());
}
```

Isto automaticamente vai retornar uma resposta `HTTP 200 OK`. Você pode especificar o status HTTP no segundo parâmetro:

```php
public function index_post()
  {
    // ...cria um novo livro
    $this->response($book, 201); // Envia HTTP 201 Created
  }
```

Se você não especificar um código de resposta e o resultado retornado for avaliado como `== FALSE` (um array ou string vazia, por exemplo), o código de resposta será automaticamente setado para `404 Not Found`:

```php
$this->response([]); // HTTP 404 Not Found
```

## Suporte multi-idioma

Se sua aplicação utiliza arquivos de idioma para dar suporte a múltiplas localidades, a classe `REST_Controller` vai automaticamente analisar o cabeçalho HTTP `Accept-Language` e prover os idiomas(s) nas actions. Esta informação pode ser encontrada no objeto `$this->response->lang`:

```php
public function __construct()
{
  parent::__construct();

  if (is_array($this->response->lang))
  {
    $this->load->language('application', $this->response->lang[0]);
  }
  else
  {
    $this->load->language('application', $this->response->lang);
  }
}
```

## Autenticação

Esta classe também implementa suporte para autenticação básica via HTTP e/ou a autenticação digest HTTP (mais segura).

Você pode habilitar a autenticação básica setando `$config['rest_auth']` para `'basic'`. A diretiva `$config['rest_valid_logiVns']` serve para você setar os usernames e passwords habilitados a acessar seu sistema. A classe vai automaticamente enviar todos os cabeçalhos corretos pra abrir o diálogo de autenticação:

```php
$config['rest_valid_logins'] = ['username' => 'password', 'outra_pessoa' => 'seguro123'];
```

Habilitar a autenticação do tipo 'digest' é igualmente simples. Configure os logins permitidos no arquivo de config, como acima e sete `$config['rest_auth']` para `'digest'`. A classe vai automaticamente enviar os cabeçalhos para habilitar a autenticação 'digest'. 

Se você está amarrando esta biblioteca em um endpoint AJAX, onde os clientes autenticam com sessões PHP, você provavelmente não vai gostar nem da autenticação básica nem da 'digest'. Neste caso, você pode dizer para a biblioteca REST qual variável de sessão PHP deve ser checada. Se a variável existir, o usuário está autorizado. É responsabilidade da sua aplicação setar tal variável. Vocẽ pode definir a variável em ``$config['auth_source']``.  Então, dizer à biblioteca para usar uma variável de sessão, setando ``$config['rest_auth']`` para ``session``.

Todos os três métodos de autenticação podem ficar mais seguros usando uma lista de IPs permitidos. Se você habilitar `$config['rest_ip_whitelist_enabled']` no seu arquivo de configuração, você pode setar uma lista de IPs permitidos.

Qualquer cliente conectando à sua API será verificado usando o array de IPs. Se eles estiverem na lista, terão o acesso permitido. Caso contrário, não. A lista de Ips é uma string separada por vírgulas:

```php
$config['rest_ip_whitelist'] = '123.456.789.0, 987.654.32.1';
```

Seus IPs locais (`127.0.0.1` e `0.0.0.0`) são permitidos por padrão.

## Chaves de API 

Em adição aos métodos de autenticação acima, a classe `REST_Controller` tambpem suporta o uso de chaves API. Habilitar é simples. Habilite no seu arquivo **config/rest.php**:

```php
$config['rest_enable_keys'] = TRUE;
```

Você vai precisar criar uma tabela de banco de dados para armazenar e acessar as chaves. `REST_Controller` vai automaticamente assumir que você possui uma tabela como esta:

```sql
CREATE TABLE `keys` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`key` VARCHAR(40) NOT NULL,
	`level` INT(2) NOT NULL,
	`ignore_limits` TINYINT(1) NOT NULL DEFAULT '0',
	`date_created` INT(11) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

A classe vai procurar por um cabeçalho HTTP com a chave da API a cada requisição. Uma chave inválida ou ausente irá resultar em um erro `HTTP 403 Forbidden`.

Por padrão, o HTTP será `X-API-KEY`. Isto pode ser configurado no arquivo **config/rest.php**.

```bash
$ curl -X POST -H "X-API-KEY: sua_chave_aqui" http://example.com/books
```

## Outra Documentação / Tutoriais

* [NetTuts: Working with RESTful Services in CodeIgniter](http://net.tutsplus.com/tutorials/php/working-with-restful-services-in-codeigniter-2/)

## Contribuições

Este projeto foi originalmente escrito por Phil Sturgeon, no entanto o seu envolvimento foi excluído visto que ele não estava mais usando-o. A partir de 20/11/2013 o desenvolvimento e suporte são feitos por Chris Kacerguis.

Pull Requests são as melhores maneiras de consertar bugs e adicionar ferramentas. Eu sei que muitos de vocês usam, então por favor, contribua se você tem melhorias a serem feitas, e eu continuarei a realizar versões com o passar do tempo.

[![GitHub license](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](https://raw.githubusercontent.com/chriskacerguis/codeigniter-restserver/master/LICENSE)
