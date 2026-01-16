package com.example.grupo5.aitherapp.activitysApp;

import android.annotation.SuppressLint;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.View;
import android.webkit.WebChromeClient;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
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

import java.util.ArrayList;
import java.util.List;
import java.util.concurrent.Executor;

public class HomeActivity extends AppCompatActivity {

    private BtleScannerMultiple bleScanner;
    private WebView webView;

    @SuppressLint("SetJavaScriptEnabled")
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_home);

        // 1. CONFIGURACIÓN DEL MAPA WEB (WebView)
        configurarWebView();

        // 2. CONFIGURACIÓN UI y NAVEGACIÓN
        setupNavegacion();
        actualizarMonedas();

        // Botones principales
        findViewById(R.id.btnVincularQR_card).setOnClickListener(v ->
                startActivity(new Intent(HomeActivity.this, VincularQRActivity.class))
        );
        findViewById(R.id.Btncoins_card).setOnClickListener(v ->
                startActivity(new Intent(HomeActivity.this, AithWalletActivity.class))
        );

        // 3. FUNCIONALIDADES DE FONDO (Huella y BLE)
        mostrarPopupHuella();
        configurarEscaneoBleSilencioso();
    }

    private void configurarWebView() {
        webView = findViewById(R.id.webviewMap);

        if (webView != null) {
            WebSettings settings = webView.getSettings();

            // Habilitar JS es obligatorio para Leaflet
            settings.setJavaScriptEnabled(true);

            // Permitir acceso a archivos locales (assets)
            settings.setAllowFileAccess(true);
            settings.setAllowContentAccess(true);

            // Mejoras de rendimiento y almacenamiento
            settings.setDomStorageEnabled(true);
            settings.setDatabaseEnabled(true);
            settings.setCacheMode(WebSettings.LOAD_DEFAULT);

            // WebChromeClient permite ver logs de JS en el Logcat (útil para depurar)
            webView.setWebChromeClient(new WebChromeClient());

            // WebViewClient mantiene la navegación dentro de la app (no abre Chrome)
            webView.setWebViewClient(new WebViewClient());

            // CARGAR EL HTML LOCAL
            // Asegúrate de haber creado la carpeta assets/ y copiado los archivos ahí
            webView.loadUrl("file:///android_asset/index.html");
        }
    }

    // Método opcional: Si en el futuro quieres pasar datos de sensores reales al mapa JS
    private void enviarDatosAlMapa(String jsonDatos) {
        if (webView != null) {
            // Llama a la función JS loadData o actualiza la variable global
            webView.evaluateJavascript("window.SERVER_DATA = " + jsonDatos + "; loadData();", null);
        }
    }

    // =========================================================================
    // LÓGICA ORIGINAL (UI, BLE, HUELLA) - SIN CAMBIOS
    // =========================================================================

    private void setupNavegacion() {
        ImageView btnHome = findViewById(R.id.nav_home);
        if(btnHome!=null) btnHome.setSelected(true);

        ImageView btnNot = findViewById(R.id.nav_bell);
        if(btnNot!=null) btnNot.setOnClickListener(v -> startActivity(new Intent(this, NotificacionesActivity.class)));

        ImageView btnPerf = findViewById(R.id.nav_profile);
        if(btnPerf!=null) btnPerf.setOnClickListener(v -> startActivity(new Intent(this, EditarPerfilActivity.class)));

        ImageView btnWalk = findViewById(R.id.nav_walk);
        if(btnWalk!=null) btnWalk.setOnClickListener(v -> startActivity(new Intent(this, WalkActivity.class)));
    }

    private void actualizarMonedas() {
        SharedPreferences prefs = getSharedPreferences("MiAppPrefs", MODE_PRIVATE);
        // Lógica de ejemplo: sumar monedas al entrar
        int coins = prefs.getInt("coinsUsuario", 0);
        // prefs.edit().putInt("coinsUsuario", coins + 10).apply();

        TextView tv = findViewById(R.id.coinNumber);
        if (tv != null) tv.setText(String.valueOf(coins));
    }

    private void configurarEscaneoBleSilencioso() {
        if (bleScanner != null) {
            bleScanner.detenerEscaneo();
            bleScanner = null;
        }
        SharedPreferences p = getSharedPreferences("SesionUsuario", MODE_PRIVATE);
        String jsonLista = p.getString("ListaSensores", "[]");
        List<PojoSensor> listaSensores = new Gson().fromJson(jsonLista, new TypeToken<List<PojoSensor>>(){}.getType());

        List<String> macs = new ArrayList<>();
        if(listaSensores != null) {
            for(PojoSensor x : listaSensores) {
                if(x.getMac() != null && x.getMac().length() == 17) {
                    macs.add(x.getMac());
                }
            }
        }

        if(macs.isEmpty()) return;

        bleScanner = new BtleScannerMultiple(this, macs, new BtleScannerMultiple.Listener() {
            @Override
            public void onSensorDetectado(String mac, int rssi, double d) {
                // Aquí podrías actualizar datos en tiempo real si quisieras
            }
            @Override
            public void onSensorDesconectado(String mac) {
                Notificador.enviarNotificacion(HomeActivity.this, mac);
            }
        });
        bleScanner.iniciarEscaneo();
    }

    private void mostrarPopupHuella() {
        SharedPreferences p = getSharedPreferences("USER_PREFS", MODE_PRIVATE);
        if (p.getBoolean("fingerprint_enabled", false) || p.getBoolean("fingerprint_declined", false)) return;

        new AlertDialog.Builder(this)
                .setTitle("Huella")
                .setMessage("¿Desea activar el acceso rápido con huella dactilar?")
                .setPositiveButton("Sí", (d,w) -> registrarHuella())
                .setNegativeButton("No", (d,w) -> p.edit().putBoolean("fingerprint_declined",true).apply())
                .show();
    }

    private void registrarHuella() {
        BiometricManager bm = BiometricManager.from(this);
        if (bm.canAuthenticate(BiometricManager.Authenticators.BIOMETRIC_STRONG) != BiometricManager.BIOMETRIC_SUCCESS) {
            Toast.makeText(this, "El dispositivo no soporta autenticación biométrica", Toast.LENGTH_SHORT).show();
            return;
        }
        Executor ex = ContextCompat.getMainExecutor(this);
        new BiometricPrompt(this, ex, new BiometricPrompt.AuthenticationCallback() {
            @Override
            public void onAuthenticationSucceeded(@NonNull BiometricPrompt.AuthenticationResult result) {
                getSharedPreferences("USER_PREFS", MODE_PRIVATE).edit().putBoolean("fingerprint_enabled", true).apply();
                Toast.makeText(HomeActivity.this, "Huella activada correctamente", Toast.LENGTH_SHORT).show();
            }
            @Override
            public void onAuthenticationError(int errorCode, @NonNull CharSequence errString) {
                Toast.makeText(HomeActivity.this, "Error: " + errString, Toast.LENGTH_SHORT).show();
            }
        }).authenticate(new BiometricPrompt.PromptInfo.Builder()
                .setTitle("Configurar Huella")
                .setSubtitle("Toca el sensor para confirmar")
                .setNegativeButtonText("Cancelar")
                .build());
    }

    @Override
    protected void onResume() {
        super.onResume();
        if (webView != null) webView.onResume(); // Importante para pausar JS si sales
        configurarEscaneoBleSilencioso();
    }

    @Override
    protected void onPause() {
        super.onPause();
        if (webView != null) webView.onPause();
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        if (bleScanner != null) bleScanner.detenerEscaneo();
        if (webView != null) webView.destroy(); // Limpiar memoria
    }
}