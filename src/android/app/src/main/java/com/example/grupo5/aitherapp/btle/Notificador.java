package com.example.grupo5.aitherapp.btle;

import android.Manifest;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.content.Context;
import android.content.pm.PackageManager;
import android.os.Build;

import androidx.core.app.ActivityCompat;
import androidx.core.app.NotificationCompat;
import androidx.core.app.NotificationManagerCompat;

import com.example.grupo5.aitherapp.R;

public class Notificador {

    private static final String CHANNEL_ID = "canal_desconexion";
    private static final String CHANNEL_NAME = "Desconexión BTLE";
    private static final String CHANNEL_DESC = "Notifica cuando un dispositivo BTLE se desconecta";

    // Evita notificaciones repetidas por la misma desconexión
    private static boolean yaNotificado = false;

    public static void resetearEstado() {
        yaNotificado = false;
    }

    public static void enviarNotificacion(Context context, String macDispositivo) {

        // Si ya avisamos de esta desconexión, no volvemos a notificar
        if (yaNotificado) return;
        yaNotificado = true;

        crearCanal(context);

        if (ActivityCompat.checkSelfPermission(context, Manifest.permission.POST_NOTIFICATIONS)
                != PackageManager.PERMISSION_GRANTED) {
            return;
        }

        NotificationCompat.Builder builder =
                new NotificationCompat.Builder(context, CHANNEL_ID)
                        .setSmallIcon(R.drawable.ic_launcher_foreground)
                        .setContentTitle("Sensor desconectado")
                        .setContentText("El dispositivo " + macDispositivo + " se ha desconectado.")
                        .setPriority(NotificationCompat.PRIORITY_HIGH)
                        .setAutoCancel(true);

        NotificationManagerCompat manager = NotificationManagerCompat.from(context);
        manager.notify((int) System.currentTimeMillis(), builder.build());
    }

    private static void crearCanal(Context context) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            NotificationChannel channel =
                    new NotificationChannel(CHANNEL_ID, CHANNEL_NAME, NotificationManager.IMPORTANCE_DEFAULT);
            channel.setDescription(CHANNEL_DESC);

            NotificationManager manager = context.getSystemService(NotificationManager.class);
            if (manager != null) {
                manager.createNotificationChannel(channel);
            }
        }
    }
}
