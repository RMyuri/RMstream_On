#!/bin/bash
echo "Iniciando servidor PHP para RMStream..."
echo ""
echo "O site estará disponível em: http://localhost:8000"
echo "Pressione Ctrl+C para parar o servidor"
echo ""
php -S localhost:8000 -t .
