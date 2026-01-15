package com.example.grupo5.aitherapp.activitysApp;

import android.annotation.SuppressLint;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.preference.PreferenceManager;
import android.view.MotionEvent;
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

// Importaciones de OSMDroid
import org.osmdroid.config.Configuration;
import org.osmdroid.tileprovider.tilesource.TileSourceFactory;
import org.osmdroid.util.GeoPoint;
import org.osmdroid.views.MapView;

import java.lang.reflect.Type;
import java.util.ArrayList;
import java.util.List;
import java.util.concurrent.Executor;

public class HomeActivity extends AppCompatActivity {

    private BtleScannerMultiple bleScanner;
    private MapView map;

    // Variable para manejar la capa de calor
    private ContaminacionOverlay overlayCalor;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        // 1. CONFIGURACIÓN OSMDROID (Antes de setContentView)
        Configuration.getInstance().load(getApplicationContext(),
                PreferenceManager.getDefaultSharedPreferences(getApplicationContext()));

        setContentView(R.layout.activity_home);

        // 2. INICIALIZAR EL MAPA Y LA CAPA DE CALOR
        setupMapa();

        // -----------------------------------------------------------
        // (Resto de tu lógica intacta)
        // -----------------------------------------------------------

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

        findViewById(R.id.btnVincularQR_card).setOnClickListener(v ->
                startActivity(new Intent(HomeActivity.this, VincularQRActivity.class))
        );

        findViewById(R.id.Btncoins_card).setOnClickListener(v ->
                startActivity(new Intent(HomeActivity.this, AithWalletActivity.class))
        );

        mostrarPopupHuella();
        configurarEscaneoBleSilencioso();
    }

    /**
     * Configura el mapa, añade el overlay de calor y carga datos falsos.
     */
    @SuppressLint("ClickableViewAccessibility")
    private void setupMapa() {
        map = findViewById(R.id.map);
        if (map != null) {
            map.setTileSource(TileSourceFactory.MAPNIK);
            map.setMultiTouchControls(true);
            map.getController().setZoom(13.0); // Un poco más lejos para ver los puntos

            // Centrar en Valencia (puedes cambiarlo)
            GeoPoint startPoint = new GeoPoint(39.4699, -0.3763);
            map.getController().setCenter(startPoint);

            // --- AÑADIMOS LA CAPA DE CALOR ---
            overlayCalor = new ContaminacionOverlay();
            map.getOverlays().add(overlayCalor);

            // Cargamos datos de prueba para ver los colores
            cargarDatosDePrueba();

            // Solución ScrollView
            map.setOnTouchListener((v, event) -> {
                int action = event.getAction();
                switch (action) {
                    case MotionEvent.ACTION_DOWN:
                        v.getParent().requestDisallowInterceptTouchEvent(true);
                        break;
                    case MotionEvent.ACTION_UP:
                        v.getParent().requestDisallowInterceptTouchEvent(false);
                        break;
                }
                return false;
            });
        }
    }

    /**
     * Método temporal para pintar puntos falsos y verificar que el mapa de calor funciona.
     */
    private void cargarDatosDePrueba() {
        if (overlayCalor == null) return;

        List<ContaminacionOverlay.PuntoContaminacion> lista = new ArrayList<>();

        // Valencia Centro (Zona Amarilla - Moderada)
        lista.add(new ContaminacionOverlay.PuntoContaminacion(39.4699, -0.3763, 0.5));

        // Puerto (Zona Verde - Limpia)
        lista.add(new ContaminacionOverlay.PuntoContaminacion(39.4580, -0.3300, 0.1));

        // Zona Industrial ficticia al oeste (Zona Roja - Alta contaminación)
        lista.add(new ContaminacionOverlay.PuntoContaminacion(39.4700, -0.4000, 0.9));

        overlayCalor.setPuntos(lista);

        // Refrescar el mapa para que pinte
        map.invalidate();
    }

    @Override
    protected void onResume() {
        super.onResume();
        if (map != null) map.onResume();
        configurarEscaneoBleSilencioso();
    }

    @Override
    protected void onPause() {
        super.onPause();
        if (map != null) map.onPause();
    }

    // -------------------------------------------------------------
    // Escaneo BLE
    // -------------------------------------------------------------
    private void configurarEscaneoBleSilencioso() {
        if (bleScanner != null) {
            bleScanner.detenerEscaneo();
            bleScanner = null;
        }

        SharedPreferences prefsSesion = getSharedPreferences("SesionUsuario", MODE_PRIVATE);
        String jsonLista = prefsSesion.getString("ListaSensores", "[]");
        Gson gson = new Gson();
        Type tipoLista = new TypeToken<List<PojoSensor>>() {}.getType();
        List<PojoSensor> sensoresAfiliados = gson.fromJson(jsonLista, tipoLista);

        List<String> macs = new ArrayList<>();
        if (sensoresAfiliados != null) {
            for (PojoSensor sensor : sensoresAfiliados) {
                if (sensor.getMac() != null && !sensor.getMac().isEmpty()) {
                    macs.add(sensor.getMac());
                }
            }
        }

        if (macs.isEmpty()) return;

        bleScanner = new BtleScannerMultiple(this, macs, new BtleScannerMultiple.Listener() {
            @Override
            public void onSensorDetectado(String mac, int rssi, double distanciaAprox) {
                // Escaneo silencioso
            }

            @Override
            public void onSensorDesconectado(String mac) {
                Notificador.enviarNotificacion(HomeActivity.this, mac);
            }
        });

        bleScanner.iniciarEscaneo();
    }

    // -------------------------------------------------------------
    // Navegación y otros
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

    private void mostrarPopupHuella() {
        SharedPreferences prefs = getSharedPreferences("USER_PREFS", MODE_PRIVATE);
        if (prefs.getBoolean("fingerprint_enabled", false) || prefs.getBoolean("fingerprint_declined", false)) return;

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
            Toast.makeText(this, "No se puede usar la huella.", Toast.LENGTH_LONG).show();
            return;
        }

        Executor executor = ContextCompat.getMainExecutor(this);
        BiometricPrompt biometricPrompt = new BiometricPrompt(this, executor,
                new BiometricPrompt.AuthenticationCallback() {
                    @Override
                    public void onAuthenticationSucceeded(@NonNull BiometricPrompt.AuthenticationResult result) {
                        super.onAuthenticationSucceeded(result);
                        getSharedPreferences("USER_PREFS", MODE_PRIVATE)
                                .edit().putBoolean("fingerprint_enabled", true).apply();
                        Toast.makeText(HomeActivity.this, "Huella activada.", Toast.LENGTH_SHORT).show();
                    }
                    @Override
                    public void onAuthenticationError(int errorCode, @NonNull CharSequence errString) {
                        super.onAuthenticationError(errorCode, errString);
                        Toast.makeText(HomeActivity.this, "Error: " + errString, Toast.LENGTH_SHORT).show();
                    }
                });

        BiometricPrompt.PromptInfo promptInfo = new BiometricPrompt.PromptInfo.Builder()
                .setTitle("Registrar huella")
                .setSubtitle("Coloca tu dedo")
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