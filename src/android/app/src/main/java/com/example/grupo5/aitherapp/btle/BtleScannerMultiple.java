package com.example.grupo5.aitherapp.btle;

import android.Manifest;
import android.bluetooth.BluetoothAdapter;
import android.bluetooth.BluetoothDevice;
import android.bluetooth.le.BluetoothLeScanner;
import android.bluetooth.le.ScanCallback;
import android.bluetooth.le.ScanFilter;
import android.bluetooth.le.ScanResult;
import android.bluetooth.le.ScanSettings;
import android.content.Context;
import android.content.pm.PackageManager;
import android.util.Log;

import androidx.core.app.ActivityCompat;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;

/**
 * Escáner BLE múltiple diseñado para detectar varios sensores afiliados por MAC.
 * Incluye:
 *  - Distancia aproximada suavizada (EMA)
 *  - Detección de desconexión (sin señal prolongada)
 *  - Callbacks para Activities
 */
public class BtleScannerMultiple {

    private static final String TAG = "BtleScannerMultiple";

    // Tiempo que debe pasar sin recibir señal para considerar desconexión (ej: 20s)
    private static final long TIEMPO_DESCONEXION_MS = 20000;

    private final Context context;
    private final List<String> macsObjetivo;
    private final Listener listener;

    private BluetoothLeScanner scanner;
    private ScanCallback callback;

    // Distancias suavizadas por sensor
    private final HashMap<String, Double> distanciasSuavizadas = new HashMap<>();

    // Timestamp de la última vez que vimos cada sensor
    private final HashMap<String, Long> ultimoVisto = new HashMap<>();

    // Estado de conexión por MAC (true = conectado / false = desconectado)
    private final HashMap<String, Boolean> estadoConexion = new HashMap<>();


    // =======================================================
    //      LISTENER PARA ENVIAR DATOS A ACTIVITIES
    // =======================================================
    public interface Listener {

        /** Se llama cada vez que un sensor afiliado es detectado */
        void onSensorDetectado(String mac, int rssi, double distanciaAprox);

        /** Se llama cuando un sensor lleva un tiempo prolongado sin emitir señal */
        void onSensorDesconectado(String mac);
    }


    // =======================================================
    //                      CONSTRUCTOR
    // =======================================================
    public BtleScannerMultiple(Context context, List<String> macs, Listener listener) {

        this.context = context.getApplicationContext();
        this.macsObjetivo = macs != null ? macs : new ArrayList<>();
        this.listener = listener;

        BluetoothAdapter adapter = BluetoothAdapter.getDefaultAdapter();

        if (adapter != null) {
            this.scanner = adapter.getBluetoothLeScanner();
        }
    }


    // =======================================================
    //                  INICIAR ESCANEO BLE
    // =======================================================
    public void iniciarEscaneo() {

        if (scanner == null) {
            Log.e(TAG, "ERROR: Escáner BLE no disponible.");
            return;
        }

        if (macsObjetivo.isEmpty()) {
            Log.w(TAG, "⚠ No hay sensores afiliados para escanear.");
            return;
        }

        // Crear ScanFilters para cada MAC
        List<ScanFilter> filtros = new ArrayList<>();

        for (String mac : macsObjetivo) {
            if (mac == null || mac.isEmpty()) continue;

            filtros.add(new ScanFilter.Builder()
                    .setDeviceAddress(mac)
                    .build());
        }

        ScanSettings settings = new ScanSettings.Builder()
                .setScanMode(ScanSettings.SCAN_MODE_LOW_LATENCY)
                .build();

        callback = new ScanCallback() {

            @Override
            public void onScanResult(int callbackType, ScanResult result) {

                BluetoothDevice device = result.getDevice();
                if (device == null) return;

                String macDetectada = device.getAddress();

                if (!macsObjetivo.contains(macDetectada)) return;

                int rssi = result.getRssi();

                // Registrar timestamp de detección
                long ahora = System.currentTimeMillis();
                ultimoVisto.put(macDetectada, ahora);

                // Marcar como conectado
                estadoConexion.put(macDetectada, true);

                // Al volver a ver el sensor, rearmamos el notificador
                Notificador.resetearEstado();

                // Calcular distancia suavizada
                double distancia = calcularDistanciaAprox(macDetectada, rssi);

                // Enviar datos a la Activity
                if (listener != null) {
                    listener.onSensorDetectado(macDetectada, rssi, distancia);
                }

                Log.d(TAG, "Detectado -> MAC=" + macDetectada +
                        " RSSI=" + rssi +
                        " Distancia=" + distancia);
            }

            @Override
            public void onScanFailed(int errorCode) {
                Log.e(TAG, "Escaneo BLE falló. Código=" + errorCode);
            }
        };

        if (ActivityCompat.checkSelfPermission(context, Manifest.permission.BLUETOOTH_SCAN)
                != PackageManager.PERMISSION_GRANTED) {
            Log.e(TAG, "Falta permiso BLUETOOTH_SCAN.");
            return;
        }

        scanner.startScan(filtros, settings, callback);

        Log.i(TAG, "Escaneo BLE iniciado.");

        // Iniciar supervisor de desconexión
        iniciarSupervisorDesconexion();
    }


    // =======================================================
    //                  DETENER ESCANEO BLE
    // =======================================================
    public void detenerEscaneo() {

        if (scanner != null && callback != null) {

            if (ActivityCompat.checkSelfPermission(context, Manifest.permission.BLUETOOTH_SCAN)
                    != PackageManager.PERMISSION_GRANTED) {
                return;
            }

            scanner.stopScan(callback);
            Log.i(TAG, "Escaneo BLE detenido.");
        }

        callback = null;
    }


    // =======================================================
    //      DISTANCIA SUAVIZADA (EMA) — MÉTODO PROFESIONAL
    // =======================================================
    private double calcularDistanciaAprox(String mac, int rssi) {

        final int txPower = -59;    // Valor típico si no está calibrado
        final double n = 2.2;       // Coeficiente ambiental interior
        final double alfa = 0.25;   // Suavizado EMA

        // Distancia inmediata (RUIDOSA)
        double distanciaInst =
                Math.pow(10d, ((double) txPower - rssi) / (10 * n));

        double anterior = distanciasSuavizadas.getOrDefault(mac, distanciaInst);

        // Suavizado
        double suavizada =
                alfa * distanciaInst +
                        (1 - alfa) * anterior;

        // Límite a valores sensatos
        if (suavizada < 0.3) suavizada = 0.3;
        if (suavizada > 25)  suavizada = 25;

        distanciasSuavizadas.put(mac, suavizada);

        return suavizada;
    }


    // =======================================================
    //              SUPERVISAR SENSORES DESCONECTADOS
    // =======================================================
    private void iniciarSupervisorDesconexion() {

        new Thread(() -> {

            while (callback != null) { // Activo mientras haya escaneo

                long ahora = System.currentTimeMillis();

                for (String mac : macsObjetivo) {

                    Long ultimo = ultimoVisto.get(mac);
                    if (ultimo == null) continue;

                    Boolean conectado = estadoConexion.get(mac);
                    if (conectado == null) conectado = false;

                    // Si estaba marcado como conectado y llevamos mucho sin verlo → desconectado
                    if (conectado && (ahora - ultimo > TIEMPO_DESCONEXION_MS)) {

                        Log.w(TAG, "Desconectado -> MAC=" + mac);

                        // Cambiamos el estado a desconectado (para no repetir evento)
                        estadoConexion.put(mac, false);

                        if (listener != null) {
                            listener.onSensorDesconectado(mac);
                        }
                    }
                }

                try {
                    Thread.sleep(2000);  // Revisar cada 2s
                } catch (InterruptedException ignored) {}
            }

        }).start();
    }

}
