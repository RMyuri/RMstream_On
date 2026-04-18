# Como Rodar o RMStream sem XAMPP

## Opção 1: PHP Built-in Server (Recomendado)

### Pré-requisitos
- PHP instalado no seu computador

### Passos
1. Abra o terminal na pasta `RMStream`
2. Execute o script `iniciar.bat` (Windows) ou `iniciar.sh` (Linux/Mac)
3. O site estará disponível em: http://localhost:8000
4. Pressione Ctrl+C para parar o servidor

### Comando manual
Se preferir executar manualmente:
```bash
php -S localhost:8000 -t .
```

## Opção 2: XAMPP (Completo)

Se você precisar do banco de dados MySQL:
1. Instale o XAMPP
2. Mova a pasta `RMStream` para `C:\xampp\htdocs\`
3. Inicie o Apache e MySQL no XAMPP
4. Acesse: http://localhost/RMStream/views/index.php

## Opção 3: Docker

Se você tem Docker instalado:
```bash
docker run -d -p 8000:80 -v $(pwd):/var/www/html php:apache
```

## Notas Importantes

- O PHP Built-in Server é ideal para desenvolvimento e testes
- Para produção, use um servidor completo (Apache/Nginx)
- O banco de dados MySQL é necessário para funcionalidades como login, chat, etc.
