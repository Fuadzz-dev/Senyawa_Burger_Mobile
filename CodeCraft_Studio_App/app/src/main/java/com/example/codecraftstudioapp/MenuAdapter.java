package com.example.codecraftstudioapp;

import android.content.Context;
import android.content.Intent;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.bumptech.glide.Glide;
import com.example.codecraftstudioapp.models.Menu;

import java.util.List;

public class MenuAdapter extends RecyclerView.Adapter<MenuAdapter.MenuViewHolder> {

    private Context context;
    private List<Menu> menuList;

    public MenuAdapter(Context context, List<Menu> menuList) {
        this.context = context;
        this.menuList = menuList;
    }

    @NonNull
    @Override
    public MenuViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.item_menu, parent, false);
        return new MenuViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull MenuViewHolder holder, int position) {
        Menu menu = menuList.get(position);
        holder.tvNamaMenu.setText(menu.getNamaMenu());
        holder.tvDeskripsi.setText(menu.getDeskripsi());
        holder.tvHarga.setText(String.format("Rp %,.0f", menu.getHarga()));

        if (menu.getFotoUrl() != null && !menu.getFotoUrl().isEmpty()) {
            Glide.with(context)
                    .load(menu.getFotoUrl())
                    .placeholder(R.mipmap.ic_launcher)
                    .error(R.mipmap.ic_launcher)
                    .into(holder.imgMenu);
        } else {
            holder.imgMenu.setImageResource(R.mipmap.ic_launcher);
        }

        holder.itemView.setOnClickListener(v -> {
            Intent intent = new Intent(context, DetailMenuActivity.class);
            intent.putExtra("ID_MENU", menu.getIdMenu());
            context.startActivity(intent);
        });
    }

    @Override
    public int getItemCount() {
        return menuList == null ? 0 : menuList.size();
    }

    public static class MenuViewHolder extends RecyclerView.ViewHolder {
        ImageView imgMenu;
        TextView tvNamaMenu, tvDeskripsi, tvHarga;

        public MenuViewHolder(@NonNull View itemView) {
            super(itemView);
            imgMenu = itemView.findViewById(R.id.imgMenu);
            tvNamaMenu = itemView.findViewById(R.id.tvNamaMenu);
            tvDeskripsi = itemView.findViewById(R.id.tvDeskripsi);
            tvHarga = itemView.findViewById(R.id.tvHarga);
        }
    }
}
