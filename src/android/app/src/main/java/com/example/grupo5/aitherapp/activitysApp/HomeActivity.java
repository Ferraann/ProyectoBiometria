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

import org.osmdroid.config.Configuration;
import org.osmdroid.tileprovider.tilesource.TileSourceFactory;
import org.osmdroid.util.GeoPoint;
import org.osmdroid.views.MapView;

import java.util.ArrayList;
import java.util.List;
import java.util.Random;
import java.util.concurrent.Executor;

public class HomeActivity extends AppCompatActivity {

    private BtleScannerMultiple bleScanner;
    private MapView map;
    private ContaminacionOverlay overlayCalor;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        // 1. Configuración OSM
        Configuration.getInstance().load(getApplicationContext(),
                PreferenceManager.getDefaultSharedPreferences(getApplicationContext()));

        setContentView(R.layout.activity_home);

        // 2. Configurar Mapa
        setupMapa();

        // 3. GENERAR DATOS FALSOS (MOCK) PARA VISUALIZAR
        generarDatosFalsos();

        // 4. Resto de UI
        setupNavegacion();
        actualizarMonedas();

        findViewById(R.id.btnVincularQR_card).setOnClickListener(v ->
                startActivity(new Intent(HomeActivity.this, VincularQRActivity.class))
        );
        findViewById(R.id.Btncoins_card).setOnClickListener(v ->
                startActivity(new Intent(HomeActivity.this, AithWalletActivity.class))
        );

        mostrarPopupHuella();
        configurarEscaneoBleSilencioso();
    }

    @SuppressLint("ClickableViewAccessibility")
    private void setupMapa() {
        map = findViewById(R.id.map);
        if (map != null) {
            map.setTileSource(TileSourceFactory.MAPNIK);
            map.setMultiTouchControls(true);
            map.getController().setZoom(13.0);

            // Centrar en Valencia
            GeoPoint centro = new GeoPoint(39.4699, -0.3763);
            map.getController().setCenter(centro);

            // Añadir capa de calor vacía
            overlayCalor = new ContaminacionOverlay();
            map.getOverlays().add(overlayCalor);

            // Solución ScrollView
            map.setOnTouchListener((v, event) -> {
                int action = event.getAction();
                if (action == MotionEvent.ACTION_DOWN) v.getParent().requestDisallowInterceptTouchEvent(true);
                if (action == MotionEvent.ACTION_UP) v.getParent().requestDisallowInterceptTouchEvent(false);
                return false;
            });
        }
    }

    /**
     * Genera 50 puntos aleatorios alrededor de Valencia para simular el mapa de calor.
     */
    private void generarDatosFalsos() {
        List<ContaminacionOverlay.PuntoContaminacion> listaMock = new ArrayList<>();
        Random random = new Random();

        // Coordenadas base (Valencia)
        double latBase = 39.4699;
        double lonBase = -0.3763;

        // Generamos 50 puntos
        for (int i = 0; i < 50; i++) {
            // Desviación aleatoria para esparcir los puntos (aprox 2-3km a la redonda)
            double latOffset = (random.nextDouble() - 0.5) * 0.04;
            double lonOffset = (random.nextDouble() - 0.5) * 0.04;

            // Intensidad aleatoria entre 0.0 (Verde) y 1.0 (Rojo)
            double intensidad = random.nextDouble();

            listaMock.add(new ContaminacionOverlay.PuntoContaminacion(
                    latBase + latOffset,
                    lonBase + lonOffset,
                    intensidad
            ));
        }

        // Puntos fijos específicos para probar colores exactos
        // Zona Industrial (Rojo intenso)
        listaMock.add(new ContaminacionOverlay.PuntoContaminacion(39.48, -0.38, 0.95));
        // Zona Puerto (Verde limpio)
        listaMock.add(new ContaminacionOverlay.PuntoContaminacion(39.46, -0.33, 0.1));
        // Zona Centro (Amarillo/Naranja)
        listaMock.add(new ContaminacionOverlay.PuntoContaminacion(39.47, -0.375, 0.6));

        // Pintar en el mapa
        if (overlayCalor != null) {
            overlayCalor.setPuntos(listaMock);
            map.invalidate(); // Refrescar visualmente
        }
    }

    // --- MÉTODOS AUXILIARES (UI, BLE, HUELLA) ---
    private void setupNavegacion() {
        ImageView btnHome = findViewById(R.id.nav_home); if(btnHome!=null) btnHome.setSelected(true);
        ImageView btnNot = findViewById(R.id.nav_bell); if(btnNot!=null) btnNot.setOnClickListener(v -> startActivity(new Intent(this, NotificacionesActivity.class)));
        ImageView btnPerf = findViewById(R.id.nav_profile); if(btnPerf!=null) btnPerf.setOnClickListener(v -> startActivity(new Intent(this, EditarPerfilActivity.class)));
        ImageView btnWalk = findViewById(R.id.nav_walk); if(btnWalk!=null) btnWalk.setOnClickListener(v -> startActivity(new Intent(this, WalkActivity.class)));
    }

    private void actualizarMonedas() {
        SharedPreferences prefs = getSharedPreferences("MiAppPrefs", MODE_PRIVATE);
        int coins = prefs.getInt("coinsUsuario", 0) + 10;
        prefs.edit().putInt("coinsUsuario", coins).apply();
        TextView tv = findViewById(R.id.coinNumber);
        if (tv != null) tv.setText(String.valueOf(coins));
    }

    private void configurarEscaneoBleSilencioso() {
        if (bleScanner != null) { bleScanner.detenerEscaneo(); bleScanner = null; }
        SharedPreferences p = getSharedPreferences("SesionUsuario", MODE_PRIVATE);
        List<PojoSensor> s = new Gson().fromJson(p.getString("ListaSensores", "[]"), new TypeToken<List<PojoSensor>>(){}.getType());
        List<String> m = new ArrayList<>();
        if(s!=null) {
            for(PojoSensor x:s) {
                // Validación básica de MAC para evitar crash
                if(x.getMac()!=null && x.getMac().length() == 17) m.add(x.getMac());
            }
        }
        if(m.isEmpty()) return;
        bleScanner = new BtleScannerMultiple(this, m, new BtleScannerMultiple.Listener() {
            public void onSensorDetectado(String mac, int rssi, double d) {}
            public void onSensorDesconectado(String mac) { Notificador.enviarNotificacion(HomeActivity.this, mac); }
        });
        bleScanner.iniciarEscaneo();
    }

    private void mostrarPopupHuella() {
        SharedPreferences p = getSharedPreferences("USER_PREFS", MODE_PRIVATE);
        if (p.getBoolean("fingerprint_enabled", false) || p.getBoolean("fingerprint_declined", false)) return;
        new AlertDialog.Builder(this).setTitle("Huella").setMessage("¿Activar?")
                .setPositiveButton("Sí", (d,w)->registrarHuella())
                .setNegativeButton("No", (d,w)->p.edit().putBoolean("fingerprint_declined",true).apply()).show();
    }

    private void registrarHuella() {
        BiometricManager bm = BiometricManager.from(this);
        if (bm.canAuthenticate(BiometricManager.Authenticators.BIOMETRIC_STRONG) != BiometricManager.BIOMETRIC_SUCCESS) return;
        Executor ex = ContextCompat.getMainExecutor(this);
        new BiometricPrompt(this, ex, new BiometricPrompt.AuthenticationCallback() {
            public void onAuthenticationSucceeded(@NonNull BiometricPrompt.AuthenticationResult r) {
                getSharedPreferences("USER_PREFS", MODE_PRIVATE).edit().putBoolean("fingerprint_enabled", true).apply();
            }
        }).authenticate(new BiometricPrompt.PromptInfo.Builder().setTitle("Huella").setNegativeButtonText("No").build());
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

    @Override
    protected void onDestroy() {
        super.onDestroy();
        if (bleScanner != null) bleScanner.detenerEscaneo();
    }
}