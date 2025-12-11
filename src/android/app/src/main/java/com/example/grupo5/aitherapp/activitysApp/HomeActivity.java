package com.example.grupo5.aitherapp.activitysApp;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.View;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;
import androidx.biometric.BiometricManager;
import androidx.biometric.BiometricPrompt;
import androidx.core.content.ContextCompat;

import com.example.grupo5.aitherapp.R;

import java.util.concurrent.Executor;

public class HomeActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_home);

        // funcionamento del toolbar

        ImageView btnHome = findViewById(R.id.nav_home);

        if (btnHome != null) {
            btnHome.setSelected(true);

            overridePendingTransition(0, 0);
        }

        ImageView btnNotificaciones = findViewById(R.id.nav_bell);

        if (btnNotificaciones != null) {
            btnNotificaciones.setOnClickListener(v -> {
                Intent intent = new Intent(HomeActivity.this, NotificacionesActivity.class);
                startActivity(intent);

                overridePendingTransition(0, 0);
            });
        }

        SharedPreferences prefs = getSharedPreferences("MiAppPrefs", MODE_PRIVATE);

        // Sumar 10 coins al iniciar la app
        int coinsUsuario = prefs.getInt("coinsUsuario", 0);
        coinsUsuario += 10;
        prefs.edit().putInt("coinsUsuario", coinsUsuario).apply();

        // Mostrar en la UI si tienes TextView
        TextView tvCoins = findViewById(R.id.coinNumber);
        if(tvCoins != null) {
            tvCoins.setText(String.valueOf(coinsUsuario));
        }

        // Botón Añadir Sensor (Ahora es un CardView)
        findViewById(R.id.btnVincularQR_card).setOnClickListener(v ->
                startActivity(new Intent(HomeActivity.this, VincularQRActivity.class))
        );

        // Botón AithWallet (Ahora es un CardView)
        findViewById(R.id.Btncoins_card).setOnClickListener(v ->
                startActivity(new Intent(HomeActivity.this, AithWalletActivity.class))
        );



        // Mostrar popup de huella solo si aún no se activó ni se rechazó
        mostrarPopupHuella();
    }

    public void botonEditarPerfil(View v) {
        startActivity(new Intent(this, EditarPerfilActivity.class));
    }

    public void botonIrNotificaciones(View v) {
        startActivity(new Intent(this, NotificacionesActivity.class));
    }


    private void mostrarPopupHuella() {
        SharedPreferences prefs = getSharedPreferences("USER_PREFS", MODE_PRIVATE);
        boolean fingerprintEnabled = prefs.getBoolean("fingerprint_enabled", false);
        boolean fingerprintDeclined = prefs.getBoolean("fingerprint_declined", false);

        // Si ya activó o rechazó, no mostrar popup
        if (fingerprintEnabled || fingerprintDeclined) return;

        new AlertDialog.Builder(this)
                .setTitle("Recordatorio")
                .setMessage("¿Quieres activar el login por huella?")
                .setCancelable(false)
                .setPositiveButton("Sí", (dialog, which) -> {
                    dialog.dismiss();
                    registrarHuella();
                })
                .setNegativeButton("No", (dialog, which) -> {
                    dialog.dismiss();
                    prefs.edit().putBoolean("fingerprint_declined", true).apply();
                    Toast.makeText(this, "Seguirás usando contraseña.", Toast.LENGTH_SHORT).show();
                })
                .show();
    }

    private void registrarHuella() {
        BiometricManager biometricManager = BiometricManager.from(this);
        if (biometricManager.canAuthenticate(BiometricManager.Authenticators.BIOMETRIC_STRONG)
                != BiometricManager.BIOMETRIC_SUCCESS) {
            Toast.makeText(this,
                    "No se puede usar la huella. Registra una huella en ajustes si quieres usarla.",
                    Toast.LENGTH_LONG).show();
            return;
        }

        Executor executor = ContextCompat.getMainExecutor(this);
        BiometricPrompt biometricPrompt = new BiometricPrompt(this, executor,
                new BiometricPrompt.AuthenticationCallback() {
                    @Override
                    public void onAuthenticationSucceeded(@NonNull BiometricPrompt.AuthenticationResult result) {
                        super.onAuthenticationSucceeded(result);
                        getSharedPreferences("USER_PREFS", MODE_PRIVATE)
                                .edit()
                                .putBoolean("fingerprint_enabled", true)
                                .apply();

                        Toast.makeText(HomeActivity.this,
                                "Huella activada correctamente.", Toast.LENGTH_SHORT).show();
                    }

                    @Override
                    public void onAuthenticationError(int errorCode, @NonNull CharSequence errString) {
                        super.onAuthenticationError(errorCode, errString);
                        Toast.makeText(HomeActivity.this,
                                "Error al registrar huella: " + errString, Toast.LENGTH_SHORT).show();
                    }
                });

        BiometricPrompt.PromptInfo promptInfo = new BiometricPrompt.PromptInfo.Builder()
                .setTitle("Registrar huella")
                .setSubtitle("Coloca tu dedo en el sensor")
                .setNegativeButtonText("Cancelar")
                .build();

        biometricPrompt.authenticate(promptInfo);
    }
}
