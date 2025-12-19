package com.example.grupo5.aitherapp.activitysApp;

import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.AdapterView;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.Spinner;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;

import com.example.grupo5.aitherapp.R;
import com.example.grupo5.aitherapp.btle.BtleScannerMultiple;
import com.example.grupo5.aitherapp.pojos.PojoSensor;
import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;

import java.lang.reflect.Type;
import java.util.ArrayList;
import java.util.List;

public class SensoresFragment extends Fragment {

    private List<PojoSensor> listaSensores = new ArrayList<>();
    private final List<String> nombresSpinner = new ArrayList<>();

    private Spinner spinnerSensores;
    private TextView tvDistanciaNodo;
    private TextView tvEstadoNodo;
    private Button btnActualizarDistancia;

    private PojoSensor sensorSeleccionado;
    private BtleScannerMultiple bleScanner;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater,
                             @Nullable ViewGroup container,
                             @Nullable Bundle savedInstanceState) {
        // Usamos tu layout fragment_sensores.xml
        return inflater.inflate(R.layout.fragment_sensores, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View view,
                              @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);

        // Referencias a vistas
        spinnerSensores = view.findViewById(R.id.spinnerSensores);
        tvDistanciaNodo = view.findViewById(R.id.tvDistanciaNodo);
        tvEstadoNodo = view.findViewById(R.id.tvEstadoNodo);
        btnActualizarDistancia = view.findViewById(R.id.btnActualizarDistancia);

        // 1. Recuperar lista de sensores guardada en SesionUsuario
        Context ctx = requireContext();
        SharedPreferences prefs = ctx.getSharedPreferences("SesionUsuario", Context.MODE_PRIVATE);
        String jsonLista = prefs.getString("ListaSensores", "[]");

        Gson gson = new Gson();
        Type tipoLista = new TypeToken<List<PojoSensor>>(){}.getType();
        List<PojoSensor> lista = gson.fromJson(jsonLista, tipoLista);
        if (lista != null) {
            listaSensores = lista;
        }

        // 2. Crear lista de nombres para el Spinner
        nombresSpinner.clear();
        for (int i = 0; i < listaSensores.size(); i++) {
            nombresSpinner.add("Sensor " + (i + 1));
        }

        // 3. Configurar Spinner
        ArrayAdapter<String> adapter = new ArrayAdapter<>(
                ctx,
                android.R.layout.simple_spinner_item,
                nombresSpinner
        );
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        spinnerSensores.setAdapter(adapter);

        // 4. Listener del Spinner
        spinnerSensores.setOnItemSelectedListener(new AdapterView.OnItemSelectedListener() {
            @Override
            public void onItemSelected(AdapterView<?> parent,
                                       View view,
                                       int position,
                                       long id) {

                if (listaSensores.isEmpty()) {
                    sensorSeleccionado = null;
                    return;
                }

                sensorSeleccionado = listaSensores.get(position);

                Log.d("SensoresFragment", "Seleccionado: MAC=" + sensorSeleccionado.getMac());

                // Reset de la info mientras no se actualiza la distancia
                tvDistanciaNodo.setText("-- m");
                tvEstadoNodo.setText("Desconocido");

                Toast.makeText(requireContext(),
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



        Button btnSoporte = view.findViewById(R.id.btnSoporte); // O view.findViewById si estás en un fragment

        btnSoporte.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                // Iniciar la nueva Activity
                Intent intent = new Intent(v.getContext(), SoporteActivity.class);
                startActivity(intent);
            }
        });
    }

    private void actualizarDistancia() {

        if (sensorSeleccionado == null) {
            Toast.makeText(requireContext(),
                    "Primero selecciona un sensor",
                    Toast.LENGTH_SHORT).show();
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
        bleScanner = new BtleScannerMultiple(requireContext(), macs, new BtleScannerMultiple.Listener() {
            @Override
            public void onSensorDetectado(String mac, int rssi, double distanciaAprox) {

                if (!isAdded()) return;

                requireActivity().runOnUiThread(() -> {
                    tvEstadoNodo.setText("Conectado ✓");

                    String texto = String.format("%.2f m", distanciaAprox);
                    tvDistanciaNodo.setText(texto);
                });
            }

            @Override
            public void onSensorDesconectado(String mac) {

                if (!isAdded()) return;

                requireActivity().runOnUiThread(() -> {
                    tvEstadoNodo.setText("Desconectado ✗");
                    tvDistanciaNodo.setText("-- m");
                });
            }
        });

        bleScanner.iniciarEscaneo();

        Toast.makeText(requireContext(),
                "Buscando el nodo seleccionado...",
                Toast.LENGTH_SHORT).show();
    }

    @Override
    public void onPause() {
        super.onPause();
        if (bleScanner != null) {
            bleScanner.detenerEscaneo();
        }
    }

    @Override
    public void onDestroyView() {
        super.onDestroyView();
        if (bleScanner != null) {
            bleScanner.detenerEscaneo();
            bleScanner = null;
        }
    }
}
