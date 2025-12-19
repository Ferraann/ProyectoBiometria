package com.example.grupo5.aitherapp.activitysApp;

import android.content.SharedPreferences;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.AdapterView;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.Spinner;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.example.grupo5.aitherapp.R;
import com.example.grupo5.aitherapp.btle.BtleScannerMultiple;
import com.example.grupo5.aitherapp.pojos.PojoSensor;
import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;

import java.lang.reflect.Type;
import java.util.ArrayList;
import java.util.List;

public class SensoresActivity extends AppCompatActivity {

    private List<PojoSensor> listaSensores;
    private List<String> nombresSpinner = new ArrayList<>();

    private Spinner spinnerSensores;
    private TextView tvDistanciaNodo;
    private TextView tvEstadoNodo;
    private Button btnActualizarDistancia;

    private PojoSensor sensorSeleccionado;
    private BtleScannerMultiple bleScanner;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.fragment_sensores); // PON AQUÍ EL NOMBRE REAL DEL LAYOUT

        spinnerSensores = findViewById(R.id.spinnerSensores);
        tvDistanciaNodo = findViewById(R.id.tvDistanciaNodo);
        tvEstadoNodo = findViewById(R.id.tvEstadoNodo);
        btnActualizarDistancia = findViewById(R.id.btnActualizarDistancia);

        // 1. Recuperar lista de sensores guardada
        SharedPreferences prefs = getSharedPreferences("SesionUsuario", MODE_PRIVATE);
        String jsonLista = prefs.getString("ListaSensores", "[]");

        Gson gson = new Gson();
        Type tipoLista = new TypeToken<List<PojoSensor>>(){}.getType();
        listaSensores = gson.fromJson(jsonLista, tipoLista);

        if (listaSensores == null) {
            listaSensores = new ArrayList<>();
        }

        // 2. Crear lista de nombres para el Spinner
        for (int i = 0; i < listaSensores.size(); i++) {
            nombresSpinner.add("Sensor " + (i + 1));
        }

        // 3. Configurar Spinner
        ArrayAdapter<String> adapter = new ArrayAdapter<>(
                this,
                android.R.layout.simple_spinner_item,
                nombresSpinner
        );
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        spinnerSensores.setAdapter(adapter);

        // 4. Listener del Spinner
        spinnerSensores.setOnItemSelectedListener(new AdapterView.OnItemSelectedListener() {
            @Override
            public void onItemSelected(AdapterView<?> parent, View view, int position, long id) {

                if (listaSensores.isEmpty()) {
                    sensorSeleccionado = null;
                    return;
                }

                sensorSeleccionado = listaSensores.get(position);

                Log.d("SensoresActivity", "Seleccionado: MAC=" + sensorSeleccionado.getMac());

                // Cada vez que cambias de sensor, reseteamos la info de distancia/estado
                tvDistanciaNodo.setText("-- m");
                tvEstadoNodo.setText("Desconocido");

                Toast.makeText(SensoresActivity.this,
                        "Seleccionado " + nombresSpinner.get(position),
                        Toast.LENGTH_SHORT).show();
            }

            @Override
            public void onNothingSelected(AdapterView<?> parent) {
                sensorSeleccionado = null;
            }
        });

        // 5. Botón ACTUALIZAR DISTANCIA
        btnActualizarDistancia.setOnClickListener(v -> actualizarDistancia());
    }

    private void actualizarDistancia() {

        if (sensorSeleccionado == null) {
            Toast.makeText(this, "Primero selecciona un sensor", Toast.LENGTH_SHORT).show();
            return;
        }

        // Detener escaneo anterior si lo hubiera
        if (bleScanner != null) {
            bleScanner.detenerEscaneo();
            bleScanner = null;
        }

        List<String> macs = new ArrayList<>();
        macs.add(sensorSeleccionado.getMac());

        // Crear escáner para SOLO el sensor seleccionado
        bleScanner = new BtleScannerMultiple(this, macs, new BtleScannerMultiple.Listener() {
            @Override
            public void onSensorDetectado(String mac, int rssi, double distanciaAprox) {

                runOnUiThread(() -> {
                    tvEstadoNodo.setText("Conectado ✓");

                    String texto = String.format("%.2f m", distanciaAprox);
                    tvDistanciaNodo.setText(texto);
                });
            }

            @Override
            public void onSensorDesconectado(String mac) {
                runOnUiThread(() -> {
                    tvEstadoNodo.setText("Desconectado ✗");
                    tvDistanciaNodo.setText("-- m");
                });
            }
        });

        bleScanner.iniciarEscaneo();

        Toast.makeText(this, "Buscando el nodo seleccionado...", Toast.LENGTH_SHORT).show();
    }

    @Override
    protected void onPause() {
        super.onPause();
        if (bleScanner != null) {
            bleScanner.detenerEscaneo();
        }
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        if (bleScanner != null) {
            bleScanner.detenerEscaneo();
        }
    }
}
