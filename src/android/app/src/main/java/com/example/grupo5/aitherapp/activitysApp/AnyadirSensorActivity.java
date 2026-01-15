package com.example.grupo5.aitherapp.activitysApp;

import android.content.Intent;
import android.os.Bundle;
import android.widget.Button;
import android.widget.ImageView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;
import androidx.cardview.widget.CardView;
import com.example.grupo5.aitherapp.R;

// 1. CORREGIDO: El nombre de la clase ahora coincide con tu archivo (con 'ny')
public class AnyadirSensorActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_anyadir_sensor);

        // --- LÓGICA DE LA PANTALLA ---

        Button btnAceptar = findViewById(R.id.btnAceptarManual);

        btnAceptar.setOnClickListener(v -> {
            // Mostramos el mensaje temporal
            Toast.makeText(AnyadirSensorActivity.this, "Esta herramienta aún no está disponible", Toast.LENGTH_SHORT).show();

            // Opcional: Si quieres borrar el texto al pulsar
            // EditText etIdManual = findViewById(R.id.etIdManual);
            // etIdManual.setText("");
        });

        // Botón de la CÁMARA (El cuadrado grande del centro)
        CardView btnCamara = findViewById(R.id.btnEscanearQR);
        btnCamara.setOnClickListener(v -> {
            // CORREGIDO: Ahora el contexto coincide con el nombre de la clase
            Intent intent = new Intent(AnyadirSensorActivity.this, VincularQRActivity.class);
            startActivity(intent);
        });

        // --- LÓGICA DE LA TOOLBAR (Barra inferior) ---

        // Botón Home
        ImageView btnHome = findViewById(R.id.nav_home);
        if (btnHome != null) {
            btnHome.setOnClickListener(v -> {
                // Volver al Home
                Intent intent = new Intent(AnyadirSensorActivity.this, HomeActivity.class);
                intent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP | Intent.FLAG_ACTIVITY_SINGLE_TOP);
                startActivity(intent);
                overridePendingTransition(0, 0);
            });
        }

        // Botón Notificaciones
        ImageView btnNotificaciones = findViewById(R.id.nav_bell);
        if (btnNotificaciones != null) {
            btnNotificaciones.setOnClickListener(v -> {
                // CORREGIDO: Cambiado HomeActivity.this por AnyadirSensorActivity.this
                Intent intent = new Intent(AnyadirSensorActivity.this, NotificacionesActivity.class);
                startActivity(intent);
                overridePendingTransition(0, 0);
            });
        }

        // Botón Perfil
        ImageView btnPerfil = findViewById(R.id.nav_profile);
        if (btnPerfil != null) {
            btnPerfil.setOnClickListener(v -> {
                Intent intent = new Intent(AnyadirSensorActivity.this, EditarPerfilActivity.class);
                startActivity(intent);
                overridePendingTransition(0, 0);
            });
        }

        // Botón Walk
        ImageView btnWalk = findViewById(R.id.nav_walk);
        if (btnWalk != null) {
            btnWalk.setOnClickListener(v -> {
                Intent intent = new Intent(AnyadirSensorActivity.this, WalkActivity.class);
                startActivity(intent);
                overridePendingTransition(0, 0);
            });
        }
    }
}