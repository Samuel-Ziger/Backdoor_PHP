# Backdoor_PHP

Projeto experimental de prova de conceito (PoC) que implementa um canal de comando remoto em PHP com cliente em Python.

O repositório contém um backdoor mínimo em PHP e um cliente em Python que cifra/encapsula comandos, envia para o servidor e exibe a saída executada no sistema alvo.

## Visão geral

Este código não é um projeto de aplicação real ou utilitário legítimo; ele foi construído como demonstração de execução remota de comandos e obfuscação em transporte.

A estrutura é simples:

- `backdoor.php`: endpoint HTTP em PHP que recebe uma requisição, valida um token de autorização, decifra o payload e executa o comando via `eval()`
- `client.py`: cliente Python que monta o comando, aplica a mesma lógica de criptografia/obfuscação, envia via `POST` e decifra a resposta

## Como funciona

### 1) Autenticação

O cliente monta um header `Authorized` no formato:

```text
senha_plana-nonce
```

Onde:

- `senha_plana` é uma string fixa definida em `client.py`
- `nonce` é um valor aleatório de 6 dígitos

O servidor valida o prefixo da credencial usando o hash MD5 da parte `senha` e compara com um valor esperado.

### 2) Criptografia

A operação usa uma variação do algoritmo RC4 para cifrar o comando que será executado.

A chave de operação é gerada com:

```text
inbound_credential_token + md5(nonce) + nonce
```

O payload é então:

- codificado em base64
- transformado com um alfabeto alternativo para dificultar a leitura em trânsito
- enviado no corpo em `p=<payload>`

### 3) Execução remota

No servidor, o PHP faz:

1. extrai a credencial do header `Authorized`
2. valida a senha
3. decifra `p=`
4. executa o comando via `eval()`
5. retorna a saída cifrada em uma resposta HTTP

## Arquivos do projeto

### `backdoor.php`

Arquivo principal do servidor.

Funções significativas:

- `computeDeterministicOutcomeDigest()`
- `applyRegionalEntropyHarmonization()`
- `normalizeSurpriseToWireCanonicalForm()`
- `materializeInboundSurpriseFromCanonicalForm()`
- `bootstrapRemoteIntegrationChannel()`

Essas funções imitavam uma "camada de compatibilidade" para esconder a lógica real do backdoor em uma estrutura pseudo-"biblioteca".

### `client.py`

Cliente em Python para interagir com o servidor PHP.

Elementos principais:

- `rc4(data, key)`
- `custom_base64_encode()`
- `custom_base64_decode()`
- envio de comandos via `requests.post()`
- decodificação da resposta do servidor

## Requisitos

- PHP instalado e executável
- Python 3
- biblioteca `requests`

Instale a dependência do cliente:

```bash
pip install requests
```

## Como rodar

### Iniciar o servidor PHP

Na raiz do projeto:

```bash
php -S localhost:8000
```

Esse comando serve o arquivo `backdoor.php` em `http://localhost:8000/backdoor.php`.

### Executar o cliente

```bash
python client.py
```

O cliente vai exibir um prompt:

```text
vm-shell>
```

Digite comandos como:

```text
whoami
id
uname -a
ls -la
```

A saída do servidor será exibida no terminal.

## Observações importantes

- Este repositório é um PoC de execução remota de comandos e deve ser usado apenas em ambientes controlados, autorizados e de teste.
- Não exponha o endpoint em rede pública.
- O uso indevido pode violar leis, políticas internas ou termos de uso.
- O código foi escrito para fins de estudo e demonstração de técnicas de obfuscação/execução remota.

## Resumo técnico

O projeto usa:

- PHP para execução local na máquina alvo
- Python para comando cliente
- RC4 customizado
- base64 com alfabeto alternativo
- header HTTP para transporte da autenticação
- `eval()` para executar instruções em ambiente PHP

## Licença

O repositório não declara uma licença explícita no metadata do GitHub. Portanto, o uso deve ser tratado como código de demonstração/estudo, sem garantia de licenciamento aberto ou uso comercial.

## Conclusão

Este projeto funciona como um exemplo de backdoor em PHP de baixa complexidade, com cliente em Python e camada de criptografia para dificultar a leitura do payload. Ele serve principalmente como POC educacional para análise de técnicas de comando remoto e obfuscação de comunicação.
