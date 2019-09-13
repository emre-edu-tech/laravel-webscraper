@foreach($car_items as $car_item)
<img src="{{ $car_item['img_src'] }}" alt="{{ $car_item['title'] }}">
@endforeach