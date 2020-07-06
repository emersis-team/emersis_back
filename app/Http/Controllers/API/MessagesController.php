<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Support\Facades\DB;

class MessagesController extends Controller
{
    public function getConversations()
    {
        $user = Auth::user();
        $conversations = array();
        $active_users = array();
        $x = 0;

        $active_conversations = Conversation::where('user_id_1', $user->id)
                        ->orWhere('user_id_2', $user->id)
                        ->with('user_1')
                        ->with('user_2')
                        ->orderBy('updated_at', 'desc')
                        ->get();

        foreach ($active_conversations as $x => $conversation) {
            $ammount_messages_no_read = count($conversation->messages_no_read->where('receiver_id',$user->id));
            $user_dest= array();

            //Identifico el usuario DESTINO de la conversacion
            if ($active_conversations[$x]->user_1->id != $user->id) {
                $user_dest = [
                    "id" => $active_conversations[$x]->user_1->id,
                    "name" => $active_conversations[$x]->user_1->name,
                    "email" => $active_conversations[$x]->user_1->email
                ];
                $active_users[$x] = $active_conversations[$x]->user_1->id;
            }else{
                $user_dest = [
                    "id" => $active_conversations[$x]->user_2->id,
                    "name" => $active_conversations[$x]->user_2->name,
                    "email" => $active_conversations[$x]->user_2->email
                ];
                $active_users[$x] = $active_conversations[$x]->user_2->id;
            }

            $ammount_messages_no_read = count($conversation->messages_no_read->where('receiver_id',$user->id));

            $conversations[$x]['id']= $active_conversations[$x]->id;
            $conversations[$x]['user_dest']= $user_dest;
            $conversations[$x]['ammount_no_read']= $ammount_messages_no_read;
        }
        //Guardo el usuario logueado dentro de los usuarios con conversación activa
        $active_users[$x+1] = $user->id;
        sort($active_users);

        //var_dump(count($active_users));

        if (count($active_users) > 1) { //Existe alguna conversacion activa
            $x++;
        }

        $inactive_users = User::whereNotIn('id', $active_users)
                        ->orderBy('name', 'asc')
                        ->get();


        foreach ($inactive_users as $inactive_user) {
            //Identifico el usuario DESTINO de la conversacion
            $user_dest = [
                "id" => $inactive_user->id,
                "name" => $inactive_user->name,
                "email" => $inactive_user->email
            ];

            $conversations[$x]['id']= 0;
            $conversations[$x]['user_dest']= $user_dest;
            $conversations[$x]['ammount_no_read']= 0;

            $x++;
        }

        return response()->json([
            'user_origin' => $user->id,
            'conversations' => $conversations,
        ]);
    }

    public function getMessagesFromUserOLD(Conversation $conversation)
    {
        $user = Auth::user();
        $messages = [];

        if ($user->id !== $conversation->user_id_1 && $user->id !== $conversation->user_id_2) {
            throw new AccessDeniedHttpException(__('No existe la conversación para el usuario.'));
        }

        //Devuelve los mensajes de una Conversacion y pone como READ todos los mjes no leidos

        $messages = Message::where('conversation_id', $conversation->id)
                            ->orderBy('created_at', 'asc')
                            ->paginate(10);

        Message::where('receiver_id',$user->id)
                ->where('read_at',NULL)
                ->update(['read_at' => now()]);

        return response()->json([
            'user_origin' => $user->id,
            'messages' => $messages,
        ]);

    }
    public function getMessagesFromConversation($request)
    {
        $user = Auth::user();
        $messages = [];
        $conversation_id = intval($request);

        try {
            // var_dump($conversation_id);
            // var_dump($request);

            if ($conversation_id > 0) {
                //Chequear que exista la conversacion y pertenezca al usuario logueado
                $conversation = Conversation::where('id', $conversation_id)
                                          ->first();

                if (!$conversation) {
                    throw new AccessDeniedHttpException(__('No existe la conversación.'));
                } else { //Existe el id de conversacion PERO NO pertenece al usuario logueado
                    if (($user->id !== $conversation->user_id_1 && $user->id !== $conversation->user_id_2)) {
                        throw new AccessDeniedHttpException(__('No existe la conversación para el usuario.'));
                    }
                }

                //Devuelve los mensajes de una Conversacion y pone como READ todos los mjes no leidos
                $messages = Message::where('conversation_id', $conversation->id)
                                 ->orderBy('created_at', 'asc')
                                 ->paginate(10);

                Message::where('receiver_id', $user->id)
                    ->where('read_at', null)
                    ->update(['read_at' => now()]);
            }

            return response()->json([
                'user_origin' => $user->id,
                'messages' => $messages,
            ]);
        }catch (\Throwable $e) {
            throw $e;
            return response()->json([
                'status' => $e->getCode() ? $e->getCode() : 500,
                'message' => $e->getMessage()
            ]);
        }

    }
    public function createMessage_OLD(Request $request, Conversation $conversation)
    {
        $user = Auth::user();

        //Chequea los campos de entrada
        $campos = $request->validate([
          'message' => 'required',
        ]);

        //var_dump($conversation->id);

        if ($user->id !== $conversation->user_id_1 && $user->id !== $conversation->user_id_2) {
          throw new AccessDeniedHttpException(__('No existe la conversación para el usuario.'));
        }

        if($conversation->user_1['id'] == $user->id){
            $receiver = $conversation->user_2['id'];
        }else{
            $receiver = $conversation->user_1['id'];
        }

        try {

          $message = Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $receiver,
            'conversation_id' => $conversation->id,
            'message' => $campos['message'],
          ]);

          if (!$message) {
            throw new \Error('No se pudo crear el mensaje.');
          }

          Conversation::where('id',$conversation->id)
                        ->update(['updated_at' => now()]);

          return response()->json([
            'status' => 200,
            'message' => 'Creación del mensaje realizada con éxito',
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            "sender_id" => $message->sender_id,
            "receiver_id" => $message->receiver_id,
            'message_created' => $message->message
            ]);

        } catch (\Throwable $e) {
          throw $e;
          return response()->json([
            'status' => $e->getCode() ? $e->getCode() : 500,
            'message' => $e->getMessage()
          ]);
        }
    }
    public function createMessage(Request $request)
    {
        $user = Auth::user();

        //Chequea los campos de entrada
        $campos = $request->validate([
          'message' => ['required','string', 'max:255'],
          'receiver_id' => ['required','integer']
        ]);

        DB::beginTransaction();
        try {
            $user_dest = User::where('id',$campos['receiver_id'])
                        ->first();
            if (!$user_dest || $user->id == $campos['receiver_id']) {
                throw new AccessDeniedHttpException(__('No se puede enviar mensaje al usuario destino.'));
            }
            $conversation = Conversation::where(function($q) use ($user, $campos){
                                            $q->where('user_id_1', $user->id);
                                            $q->where('user_id_2', $campos['receiver_id']);
                                        })
                                        ->orWhere(function($q) use ($user, $campos){
                                            $q->where('user_id_2', $user->id);
                                            $q->where('user_id_1', $campos['receiver_id']);
                                        })
                                        ->first();

            if (!$conversation){ //La conversacion NO existe, se crea antes del mensaje
                $conversation = Conversation::create([
                    'user_id_1' => $user->id,
                    'user_id_2' => $campos['receiver_id'],
                    ]);

                if (!$conversation) {
                   throw new \Error('No se pudo crear la conversación.');
                }
            }

            // Crea el mensaje y lo asocia a la conversacion
            $message = Message::create([
                'sender_id' => $user->id,
                'receiver_id' => (int) $campos['receiver_id'],
                'conversation_id' => $conversation->id,
                'message' => $campos['message'],
            ]);

            if (!$message) {
                throw new \Error('No se pudo crear el mensaje.');
            }

            Conversation::where('id',$conversation->id)
                        ->update(['updated_at' => now()]);

            DB::commit();
            return response()->json([
                'status' => 200,
                'message' => 'Creación del mensaje realizada con éxito',
                'conversation_id' => $message->conversation_id,
                'message_id' => $message->id,
                "sender_id" => $message->sender_id,
                "receiver_id" => $message->receiver_id,
                'message_created' => $message->message
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
            return response()->json([
                'status' => $e->getCode() ? $e->getCode() : 500,
                'message' => $e->getMessage()
            ]);
        }
    }
}
