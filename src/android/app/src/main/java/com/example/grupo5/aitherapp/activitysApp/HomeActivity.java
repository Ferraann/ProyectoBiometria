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
import com.example.grupo5.aitherapp.btle.BtleScannerMultiple;
import com.example.grupo5.aitherapp.btle.Notificador;
import com.example.grupo5.aitherapp.pojos.PojoSensor;
import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;

import java.lang.reflect.Type;
import java.util.ArrayList;
import java.util.List;
import java.util.concurrent.Executor;

public class HomeActivity extends AppCompatActivity {

    private BtleScannerMultiple bleScanner;

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

        ImageView btnPerfil = findViewById(R.id.nav_profile);

        if (btnPerfil != null) {
            btnPerfil.setOnClickListener(v -> {
                Intent intent = new Intent(HomeActivity.this, EditarPerfilActivity.class);
                startActivity(intent);

                overridePendingTransition(0, 0);
            });
        }

        ImageView btnWalk = findViewById(R.id.nav_walk);

        if (btnWalk != null) {
            btnWalk.setOnClickListener(v -> {
                Intent intent = new Intent(HomeActivity.this, WalkActivity.class);
                startActivity(intent);
                overridePendingTransition(0, 0);
            });
        }

        SharedPreferences prefs = getSharedPreferences("MiAppPrefs", MODE_PRIVATE);

        int coinsUsuario = prefs.getInt("coinsUsuario", 0);
        coinsUsuario += 10;
        prefs.edit().putInt("coinsUsuario", coinsUsuario).apply();

        TextView tvCoins = findViewById(R.id.coinNumber);
        if (tvCoins != null) {
            tvCoins.setText(String.valueOf(coinsUsuario));
        }

        // Botón Añadir Sensor (Ahora es un CardView)
        findViewById(R.id.btnVincularQR_card).setOnClickListener(v ->
                startActivity(new Intent(HomeActivity.this, AnyadirSensorActivity.class))
        );

        // Botón AithWallet (Ahora es un CardView)
        findViewById(R.id.Btncoins_card).setOnClickListener(v ->
                startActivity(new Intent(HomeActivity.this, AithWalletActivity.class))
        );

        // Popup huella (solo si procede)
        mostrarPopupHuella();

        // Primer arranque de escaneo BLE silencioso
        configurarEscaneoBleSilencioso();
    }

    @Override
    protected void onResume() {
        super.onResume();
        // Al volver a Home (por ejemplo tras escanear un QR), recargamos sensores y escaneo
        configurarEscaneoBleSilencioso();
    }

    // -------------------------------------------------------------
    // Configura e inicia el escaneo BLE silencioso según sensores afiliados
    // -------------------------------------------------------------
    private void configurarEscaneoBleSilencioso() {

        // Si ya había un escáner activo, lo detenemos antes de reconfigurar
        if (bleScanner != null) {
            bleScanner.detenerEscaneo();
            bleScanner = null;
        }

        // 1. Recuperar sensores afiliados guardados en sesión
        SharedPreferences prefsSesion = getSharedPreferences("SesionUsuario", MODE_PRIVATE);
        String jsonLista = prefsSesion.getString("ListaSensores", "[]");

        // 2. Convertir JSON → Lista de sensores
        Gson gson = new Gson();
        Type tipoLista = new TypeToken<List<PojoSensor>>() {}.getType();
        List<PojoSensor> sensoresAfiliados = gson.fromJson(jsonLista, tipoLista);

        // 3. Extraer la MAC de cada sensor
        List<String> macs = new ArrayList<>();
        if (sensoresAfiliados != null) {
            for (PojoSensor sensor : sensoresAfiliados) {
                if (sensor.getMac() != null && !sensor.getMac().isEmpty()) {
                    macs.add(sensor.getMac());
                }
            }
        }

        // Si no hay sensores afiliados, no iniciamos escaneo (usuario recién registrado sin QR)
        if (macs.isEmpty()) {
            return;
        }

        // 4. Crear escáner BLE
        bleScanner = new BtleScannerMultiple(this, macs, new BtleScannerMultiple.Listener() {

            @Override
            public void onSensorDetectado(String mac, int rssi, double distanciaAprox) {
                // Escaneo silencioso:
                // Aquí podrías guardar estado, loguear, etc., si lo necesitas.
            }

            @Override
            public void onSensorDesconectado(String mac) {
                // Notificación controlada (solo una vez por desconexión)
                Notificador.enviarNotificacion(HomeActivity.this, mac);
            }
        });

        // 5. Iniciar escaneo
        bleScanner.iniciarEscaneo();
    }

    // -------------------------------------------------------------
    // Navegación
    // -------------------------------------------------------------
    public void botonEditarPerfil(View v) {
        startActivity(new Intent(this, EditarPerfilActivity.class));
    }

    public void botonIrNotificaciones(View v) {
        startActivity(new Intent(this, NotificacionesActivity.class));
    }

    public void botonIrSensores(View v) {
        startActivity(new Intent(this, SensoresActivity.class));
    }

    // -------------------------------------------------------------
    // Huella / Biometría
    // -------------------------------------------------------------
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

    @Override
    protected void onDestroy() {
        super.onDestroy();

        if (bleScanner != null) {
            bleScanner.detenerEscaneo();
        }
    }
}
