package com.example.grupo5.aitherapp.activitysApp;

import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.os.Build;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.os.VibrationEffect;
import android.os.Vibrator;
import android.view.View;
import android.widget.ImageView;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.app.ActivityCompat;
import androidx.core.app.NotificationCompat;
import androidx.core.app.NotificationManagerCompat;

import com.example.grupo5.aitherapp.R;
// ------------------------------------------------------------------
// Fichero: NotificacionesActivity.java
// Autor: Pablo Chasi
// Fecha: 28/10/2025
// ------------------------------------------------------------------
// Descripción:
// Clase encargada de gestionar la creación y envío de notificaciones
// dentro de la aplicación. Incluye:
//  - Creación del canal de notificaciones (Android 8+)
//  - Envío de una alerta de peligro extremo
//  - Activación de patrones de vibración personalizados
// La actividad también controla permisos y configura acciones que se
// ejecutan al pulsar sobre la notificación (PendingIntent).
// ------------------------------------------------------------------
public class NotificacionesActivity extends AppCompatActivity {
    private static final String MY_CHANNEL_ID = "Aither";
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_notificaciones);
        crearCanal();
        configurarToolbar();
    }

    //Método nuevo para gestionar la barra de abajo
    private void configurarToolbar() {
        // 1. Resaltar el icono de la CAMPANA (porque estamos en notificaciones)
        ImageView btnBell = findViewById(R.id.nav_bell);
        if (btnBell != null) {
            btnBell.setSelected(true); // Esto activa el selector (relleno)
        }

        // 2. Dar vida al botón de CASA para volver al Home
        ImageView btnHome = findViewById(R.id.nav_home);
        if (btnHome != null) {
            btnHome.setOnClickListener(v -> {
                Intent intent = new Intent(NotificacionesActivity.this, HomeActivity.class);
                // Estas flags evitan que se acumulen pantallas si vas y vienes muchas veces
                intent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP | Intent.FLAG_ACTIVITY_SINGLE_TOP);
                startActivity(intent);

                overridePendingTransition(0, 0);
            });
        }

        // 3. Dar vida al botón de PERFIL
        ImageView btnPerfil = findViewById(R.id.nav_profile);
        if (btnPerfil != null) {
            btnPerfil.setOnClickListener(v -> {
                Intent intent = new Intent(NotificacionesActivity.this, EditarPerfilActivity.class);
                startActivity(intent);

                overridePendingTransition(0, 0);
            });
        }
    }

    public void crearCanal(){
        if(Build.VERSION.SDK_INT >= Build.VERSION_CODES.O){
            NotificationChannel channel = new NotificationChannel(MY_CHANNEL_ID, "MyCanal", NotificationManager.IMPORTANCE_DEFAULT);
            channel.enableVibration(true);
            channel.setVibrationPattern(new long[]{0, 500, 500, 500});
            NotificationManager notificationManager = (NotificationManager) getSystemService(Context.NOTIFICATION_SERVICE);
            notificationManager.createNotificationChannel(channel);
        }
    }
    public void crearNotificacionPeligroExtremo(View v){
        Intent intent = new Intent(this, NotificacionesActivity.class);
        intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK );
        intent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TASK);

        int flag = 0;
        if(Build.VERSION.SDK_INT >= Build.VERSION_CODES.M){
            flag = PendingIntent.FLAG_IMMUTABLE;
        }

        PendingIntent pendingIntent = PendingIntent.getActivity(this,0,intent,flag);

        NotificationCompat.Builder builder = new NotificationCompat.Builder(this, MY_CHANNEL_ID)
//                .setSmallIcon(R.drawable.logo_aither)
                .setContentTitle("Peligro")
                .setContentIntent(pendingIntent)
                .setContentText("Cuidado estás es una zona demasiada contaminada")
                .setPriority(NotificationCompat.PRIORITY_DEFAULT);


        if (ActivityCompat.checkSelfPermission(this, android.Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) {
            return;
        }

        NotificationManagerCompat.from(this).notify(1,builder.build());

        Vibrator vibrator = (Vibrator) getSystemService(Context.VIBRATOR_SERVICE);
        long[] pattern = new long[]{0, 500, 500, 500}; // patrón de vibración

        if (vibrator != null) {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                VibrationEffect effect = VibrationEffect.createWaveform(pattern, 0);
                vibrator.vibrate(effect);
            } else {
                vibrator.vibrate(pattern, 0);
            }

            long tiempoVibrandoMs = 10000; // 10 segundos, ajusta a lo que quieras

            new Handler(Looper.getMainLooper()).postDelayed(new Runnable() {
                @Override
                public void run() {
                    vibrator.cancel();
                }
            }, tiempoVibrandoMs);
        }
    }

//    public void crearNotificacionCuidado(View v){
//        Intent intent = new Intent(this, NotificacionesActivity.class);
//        intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
//        intent.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TASK);
//
//        int flag = 0;
//        if(Build.VERSION.SDK_INT >= Build.VERSION_CODES.M){
//            flag = PendingIntent.FLAG_IMMUTABLE;
//        }
//
//        PendingIntent pendingIntent = PendingIntent.getActivity(this, 0, intent, flag);
//
//        NotificationCompat.Builder builder = new NotificationCompat.Builder(this, MY_CHANNEL_ID)
//                .setSmallIcon(android.R.drawable.ic_delete)
//                .setContentTitle("Atención")
//                .setContentIntent(pendingIntent)
//                .setContentText("Ten cuidado te estás acercando a un sitio peligroso")
//                .setPriority(NotificationCompat.PRIORITY_DEFAULT);
//
//        if (ActivityCompat.checkSelfPermission(this, android.Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) {
//            return;
//        }
//
//        NotificationManagerCompat.from(this).notify(1, builder.build());
//
//        Vibrator vibrator = (Vibrator) getSystemService(Context.VIBRATOR_SERVICE);
//        long[] pattern = new long[]{0, 500}; // Solo vibra una vez
//
//        if (vibrator != null) {
//            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
//                VibrationEffect effect = VibrationEffect.createWaveform(pattern, -1); // -1 = no repetir
//                vibrator.vibrate(effect);
//            } else {
//                vibrator.vibrate(pattern, -1); // -1 = no repetir
//            }
//        }
//    }
}
