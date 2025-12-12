package com.example.grupo5.aitherapp.activitysApp;

import android.content.Intent;
import android.os.Bundle;
import android.widget.ImageView;

import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.fragment.app.Fragment;
import androidx.viewpager2.adapter.FragmentStateAdapter;
import androidx.viewpager2.widget.ViewPager2;

import com.example.grupo5.aitherapp.R;
import com.google.android.material.tabs.TabLayout;
import com.google.android.material.tabs.TabLayoutMediator;

public class EditarPerfilActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_editar_perfil); // Usaremos el XML nuevo en el paso 2

        TabLayout tabLayout = findViewById(R.id.tabLayoutConfig);
        ViewPager2 viewPager = findViewById(R.id.viewPagerConfig);

        // CONFIGURAR EL ADAPTADOR (EL PUENTE)
        viewPager.setAdapter(new FragmentStateAdapter(this) {
            @NonNull
            @Override
            public Fragment createFragment(int position) {
                if (position == 0) {
                    return new PerfilFragment(); // Carga la pestaña 1
                } else {
                    return new SensoresFragment(); // Carga la pestaña 2
                }
            }

            @Override
            public int getItemCount() {
                return 2; // Dos pestañas
            }
        });

        // VINCULAR PESTAÑAS
        new TabLayoutMediator(tabLayout, viewPager, (tab, position) -> {
            if (position == 0) tab.setText("Perfil");
            else tab.setText("Sensores");
        }).attach();

        configurarToolbar();
    }

    private void configurarToolbar() {
        ImageView btnPerfil = findViewById(R.id.nav_profile);
        if (btnPerfil != null) btnPerfil.setSelected(true);

        ImageView btnHome = findViewById(R.id.nav_home);
        if (btnHome != null) {
            btnHome.setOnClickListener(v -> {
                Intent intent = new Intent(EditarPerfilActivity.this, HomeActivity.class);
                intent.addFlags(Intent.FLAG_ACTIVITY_REORDER_TO_FRONT);
                startActivity(intent);
                overridePendingTransition(0, 0);
            });
        }

        ImageView btnBell = findViewById(R.id.nav_bell);
        if (btnBell != null) {
            btnBell.setOnClickListener(v -> {
                Intent intent = new Intent(EditarPerfilActivity.this, NotificacionesActivity.class);
                intent.addFlags(Intent.FLAG_ACTIVITY_REORDER_TO_FRONT);
                startActivity(intent);
                overridePendingTransition(0, 0);
            });
        }

        ImageView btnWalk = findViewById(R.id.nav_walk); // Asegúrate de que el ID en tu XML sea nav_walk
        if (btnWalk != null) {
            btnWalk.setOnClickListener(v -> {
                Intent intent = new Intent(EditarPerfilActivity.this, WalkActivity.class);
                intent.addFlags(Intent.FLAG_ACTIVITY_REORDER_TO_FRONT);
                startActivity(intent);
                overridePendingTransition(0, 0);
            });
        }




    }
}