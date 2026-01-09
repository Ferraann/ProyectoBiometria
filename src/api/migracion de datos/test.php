<?php
require_once 'conexion.php';
require_once 'conexionPrueba.php';

echo "Probando conexión DESTINO (Aither): ";
$dest = abrirServidor();
echo $dest ? "✅ OK<br>" : "❌ FALLÓ<br>";

echo "Probando conexión ORIGEN (Prueba): ";
$orig = abrirServidorpruebas();
echo $orig ? "✅ OK<br>" : "❌ FALLÓ<br>";