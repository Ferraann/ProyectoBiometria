package com.example.grupo5.aitherapp.activitysApp;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.ImageView;
import android.widget.LinearLayout;

import androidx.appcompat.app.AppCompatActivity;
import com.example.grupo5.aitherapp.R;

public class WalkActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_walk); // Usaremos activity_walk.xml

        // Funcionalidad de la Toolbar

        // 1. Marcar el icono de Walk/Caminata como seleccionado
        ImageView btnWalk = findViewById(R.id.nav_walk);
        if (btnWalk != null) {
            btnWalk.setSelected(true);
            // Evita la animación si vienes de otra actividad de la toolbar
            overridePendingTransition(0, 0);
        }

        // 2. Navegación a las otras pestañas
        ImageView btnHome = findViewById(R.id.nav_home);
        if (btnHome != null) {
            btnHome.setOnClickListener(v -> {
                Intent intent = new Intent(WalkActivity.this, HomeActivity.class);
                startActivity(intent);
                overridePendingTransition(0, 0);
            });
        }

        ImageView btnNotificaciones = findViewById(R.id.nav_bell);
        if (btnNotificaciones != null) {
            btnNotificaciones.setOnClickListener(v -> {
                Intent intent = new Intent(WalkActivity.this, NotificacionesActivity.class);
                startActivity(intent);
                overridePendingTransition(0, 0);
            });
        }

        ImageView btnPerfil = findViewById(R.id.nav_profile);
        if (btnPerfil != null) {
            btnPerfil.setOnClickListener(v -> {
                Intent intent = new Intent(WalkActivity.this, EditarPerfilActivity.class);
                startActivity(intent);
                overridePendingTransition(0, 0);
            });
        }

        // --- LÓGICA DESPLEGABLE LEYENDA ---
        LinearLayout headerLeyenda = findViewById(R.id.layoutLeyendaHeader);
        LinearLayout contentLeyenda = findViewById(R.id.layoutLeyendaContent);
        ImageView imgFlecha = findViewById(R.id.imgFlechaLeyenda);

        if (headerLeyenda != null && contentLeyenda != null) {
            headerLeyenda.setOnClickListener(v -> {
                if (contentLeyenda.getVisibility() == View.VISIBLE) {
                    // Si está visible, lo ocultamos
                    contentLeyenda.setVisibility(View.GONE);
                    imgFlecha.animate().rotation(0).setDuration(300).start(); // Flecha abajo
                } else {
                    // Si está oculto, lo mostramos
                    contentLeyenda.setVisibility(View.VISIBLE);
                    imgFlecha.animate().rotation(180).setDuration(300).start(); // Flecha arriba
                }
            });
        }
    }
}
