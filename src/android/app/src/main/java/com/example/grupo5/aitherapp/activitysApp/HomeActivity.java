package com.example.grupo5.aitherapp.activitysApp;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.appcompat.app.AppCompatActivity;

import com.example.grupo5.aitherapp.R;
// ------------------------------------------------------------------
// Fichero: HomeActivity.java
// Autor: Pablo Chasi
// Fecha: 28/10/2025
// ------------------------------------------------------------------
// Descripción:
// Clase que actúa como pantalla principal del usuario tras iniciar
// sesión. Muestra un mensaje de bienvenida, permite acceder a la
// vinculación de sensores por QR, editar el perfil y ver notificaciones.
// ------------------------------------------------------------------

public class HomeActivity extends AppCompatActivity {
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_home);


        ImageView btnHome = findViewById(R.id.nav_home);
        if (btnHome != null) {
            btnHome.setSelected(true);
        }

        // ----------------------
        // TU CÓDIGO ORIGINAL
        // ----------------------
        TextView bienvenida = findViewById(android.R.id.text1); // cámbialo si tu TextView tiene otro ID

        String emailUsuario = getIntent().getStringExtra("email_usuario");
        if (emailUsuario != null) {
            bienvenida.setText("¡Bienvenido, " + emailUsuario + "!");
        }

        // -----------------------------------------------------
        // Botón Vincular QR
        // -----------------------------------------------------
        findViewById(R.id.btnVincularQR).setOnClickListener(v -> {
            startActivity(new Intent(HomeActivity.this, VincularQRActivity.class));
        });

        // ----------------------
        // RECORDATORIO DE HUELLA
        // ----------------------
//        showFingerprintReminder();
    }

    public void botonEditarPerfil(View v) {
        Intent intent = new Intent(this, EditarPerfilActivity.class);
        startActivity(intent);
    }

    public void botonIrNotificaciones(View v) {
        Intent intent = new Intent(this, NotificacionesActivity.class);
        startActivity(intent);
    }

    public void botonIrSensores(View v) {
        Intent intent = new Intent(this, SensoresActivity.class);
        startActivity(intent);
    }
//
//    private void showFingerprintReminder() {
//        SharedPreferences sharedPref = getSharedPreferences("USER_PREFS", MODE_PRIVATE);
//        boolean fingerprintEnabled = sharedPref.getBoolean("fingerprint_enabled", false);
//        boolean fingerprintDeclined = sharedPref.getBoolean("fingerprint_declined", false);
//
//        // Si ya está activada o ya dijo que NO → no preguntar más
//        if (fingerprintEnabled || fingerprintDeclined) return;
//
//        new AlertDialog.Builder(this)
//                .setTitle("Recordatorio")
//                .setMessage("¿Quieres activar el login por huella?")
//                .setCancelable(false)
//                .setPositiveButton("Sí", (dialog, which) -> {
//                    dialog.dismiss();
//
//                    BiometricManager biometricManager = BiometricManager.from(this);
//                    if (biometricManager.canAuthenticate(BiometricManager.Authenticators.BIOMETRIC_STRONG)
//                            == BiometricManager.BIOMETRIC_SUCCESS) {
//                        showBiometricPromptForRegistration();
//                    } else {
//                        Toast.makeText(this,
//                                "No se puede usar la huella. Registra una huella en ajustes si quieres usarla.",
//                                Toast.LENGTH_LONG).show();
//                    }
//                })
//                .setNegativeButton("No", (dialog, which) -> {
//                    dialog.dismiss();
//                    sharedPref.edit().putBoolean("fingerprint_declined", true).apply();
//                    Toast.makeText(this, "Seguirás usando contraseña.", Toast.LENGTH_SHORT).show();
//                })
//                .show();
//    }
//
//    private void showBiometricPromptForRegistration() {
//        Executor executor = ContextCompat.getMainExecutor(this);
//
//        BiometricPrompt biometricPrompt = new BiometricPrompt(this, executor,
//                new BiometricPrompt.AuthenticationCallback() {
//
//                    @Override
//                    public void onAuthenticationSucceeded(
//                            @NonNull BiometricPrompt.AuthenticationResult result) {
//                        super.onAuthenticationSucceeded(result);
//
//                        getSharedPreferences("USER_PREFS", MODE_PRIVATE)
//                                .edit()
//                                .putBoolean("fingerprint_enabled", true)
//                                .apply();
//
//                        Toast.makeText(HomeActivity.this,
//                                "Huella activada correctamente.",
//                                Toast.LENGTH_SHORT).show();
//                    }
//
//                    @Override
//                    public void onAuthenticationError(int errorCode,
//                                                      @NonNull CharSequence errString) {
//                        super.onAuthenticationError(errorCode, errString);
//                        Toast.makeText(HomeActivity.this,
//                                "Error al registrar huella: " + errString,
//                                Toast.LENGTH_SHORT).show();
//                    }
//                });
//
//        BiometricPrompt.PromptInfo promptInfo =
//                new BiometricPrompt.PromptInfo.Builder()
//                        .setTitle("Registrar huella")
//                        .setSubtitle("Coloca tu dedo en el sensor")
//                        .setNegativeButtonText("Cancelar")
//                        .build();
//
//        biometricPrompt.authenticate(promptInfo);
//    }
//

//    public void botonVerSensoresAfiliados(View v){
//        Intent i = new Intent(this, ListaSensoresActivity.class);
//        startActivity(i);
//    }
}
