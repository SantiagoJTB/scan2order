#!/bin/bash

echo "🚀 Iniciando Scan2Order..."
echo ""

# Check if Docker is available
if ! command -v docker &> /dev/null; then
    echo "❌ Docker no está instalado. Por favor instala Docker."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose no está instalado. Por favor instala Docker Compose."
    exit 1
fi

echo "📦 Levantando servicios..."
docker-compose up -d

echo ""
echo "⏳ Esperando a que se inicialicen los servicios..."
sleep 10

echo ""
echo "✅ Servicios levantados exitosamente!"
echo ""
echo "🌐 Acceso a la aplicación:"
echo "   Frontend: http://localhost:8080"
echo "   Backend API: http://localhost:8080/api"
echo "   PostgreSQL: localhost:5432"
echo ""
echo "📊 Logs en tiempo real:"
echo "   docker-compose logs -f"
echo ""
echo "🛑 Para detener los servicios:"
echo "   docker-compose down"
echo ""
