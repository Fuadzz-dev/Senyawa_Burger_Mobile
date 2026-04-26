package com.example.codecraftstudioapp;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.bumptech.glide.Glide;
import com.example.codecraftstudioapp.models.CartItem;

import java.util.List;

public class CartAdapter extends RecyclerView.Adapter<CartAdapter.CartViewHolder> {

    private Context context;
    private List<CartItem> cartList;

    public CartAdapter(Context context, List<CartItem> cartList) {
        this.context = context;
        this.cartList = cartList;
    }

    @NonNull
    @Override
    public CartViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(context).inflate(R.layout.item_cart, parent, false);
        return new CartViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull CartViewHolder holder, int position) {
        CartItem item = cartList.get(position);
        holder.tvName.setText(item.getName());
        holder.tvPrice.setText(String.format("Rp %,.0f", item.getPrice()));
        holder.tvQty.setText("x" + item.getQty());
        
        String notes = item.getNotes() != null && !item.getNotes().isEmpty() ? item.getNotes() : "-";
        holder.tvNotes.setText("Catatan: " + notes);

        if (item.getImage() != null && !item.getImage().isEmpty()) {
            Glide.with(context)
                    .load(item.getImage())
                    .placeholder(R.mipmap.ic_launcher)
                    .error(R.mipmap.ic_launcher)
                    .into(holder.imgMenu);
        } else {
            holder.imgMenu.setImageResource(R.mipmap.ic_launcher);
        }
    }

    @Override
    public int getItemCount() {
        return cartList == null ? 0 : cartList.size();
    }

    public static class CartViewHolder extends RecyclerView.ViewHolder {
        ImageView imgMenu;
        TextView tvName, tvPrice, tvQty, tvNotes;

        public CartViewHolder(@NonNull View itemView) {
            super(itemView);
            imgMenu = itemView.findViewById(R.id.imgCartMenu);
            tvName = itemView.findViewById(R.id.tvCartMenuName);
            tvPrice = itemView.findViewById(R.id.tvCartMenuPrice);
            tvQty = itemView.findViewById(R.id.tvCartMenuQty);
            tvNotes = itemView.findViewById(R.id.tvCartMenuNotes);
        }
    }
}
