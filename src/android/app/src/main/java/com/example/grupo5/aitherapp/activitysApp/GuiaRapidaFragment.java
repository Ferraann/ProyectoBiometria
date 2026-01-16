package com.example.grupo5.aitherapp.activitysApp;

import android.app.DownloadManager;
import android.content.Context;
import android.net.Uri;
import android.os.Bundle;
import android.os.Environment;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageButton;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;

import com.example.grupo5.aitherapp.R;

public class GuiaRapidaFragment extends Fragment {
    @Override
    public View onCreateView(LayoutInflater inflater, ViewGroup container, Bundle savedInstanceState) {
        return inflater.inflate(R.layout.fragment_guia_rapida, container, false);
    }
    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);

        ImageButton btnDescarga1 = view.findViewById(R.id.btnDescargarGuia1);
        ImageButton btnDescarga2 = view.findViewById(R.id.btnDescargarGuia2);

        // --- BOTÓN 1 ---
        btnDescarga1.setOnClickListener(v -> {
            // URL DE PLESK
            String urlPdf = "https://fsanpra.upv.edu.es/src/img/ComoVincularTuSensor.pdf";
            descargarPDF(urlPdf, "ComoVincularTuSensor.pdf");
        });

        // --- BOTÓN 2 ---
        btnDescarga1.setOnClickListener(v -> {
            // URL DE PLESK
            String urlPdf = "https://fsanpra.upv.edu.es/src/img/ManualUsuario.pdf";
            descargarPDF(urlPdf, "ManualUsuario.pdf");
        });
    }

    // --- FUNCIÓN PARA DESCARGAR ---
    private void descargarPDF(String url, String nombreArchivo) {
        try {
            DownloadManager.Request request = new DownloadManager.Request(Uri.parse(url));

            // Permitir descargar por WiFi o Datos
            request.setAllowedNetworkTypes(DownloadManager.Request.NETWORK_WIFI | DownloadManager.Request.NETWORK_MOBILE);

            // Título de la notificación
            request.setTitle("Descargando Guía");
            request.setDescription("Descargando " + nombreArchivo);

            // Mostrar notificación mientras descarga y al terminar
            request.setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED);

            // Guardar en la carpeta pública de Descargas del móvil
            request.setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, nombreArchivo);

            // Obtener el servicio de descarga y encolar
            DownloadManager manager = (DownloadManager) requireContext().getSystemService(Context.DOWNLOAD_SERVICE);
            if (manager != null) {
                manager.enqueue(request);
                Toast.makeText(getContext(), "Descarga iniciada...", Toast.LENGTH_SHORT).show();
            }
        } catch (Exception e) {
            Toast.makeText(getContext(), "Error al descargar: " + e.getMessage(), Toast.LENGTH_LONG).show();
        }
    }
}