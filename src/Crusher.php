<?php namespace Visualplus\Crusher;

use Illuminate\Console\Command;
use Carbon\Carbon;
use DB;
use Visualplus\Crusher\CrushSchedule;
use Visualplus\Crusher\CrushScheduleLog;

class Crusher extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crusher';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '개인정보 삭제 루틴';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $debug_mode     = config('crusher.debug');
        $logging_mode   = config('crusher.log');
        $rules          = config('crusher.rules');

        foreach ($rules as $rule => $ruleset) {
            foreach ($ruleset as $term => $options) {
                $lists = CrushSchedule::where('rule', '=', $rule)
                    ->where('created_at', '>=', Carbon::now()->subMonths($term)->format('Y-m-d H:i:s'))
                    ->where('progressed_that', '<', $term)
                    ->get();

                foreach ($lists as $list) {
                    $MODEL = new $options['model'];
                    $foreign_key = $options['key'];
                    $action = $options['action'];
                    $relation = $options['relation'];

                    // 룰셋에 맞는 정보들을 가져옴
                    $records = $MODEL::where($foreign_key, '=', $list->userno)->get();

                    foreach ($records as $record) {
                        $log = new CrushScheduleLog();
                        $log->userno        = $list->userno;
                        $log->rule          = serialize($options);
                        $log->term          = $term;
                        $log->table_name    = $record->getTable();
                        $log->before_data   = serialize($record);

                        // 레코드 즉시 삭제
                        if ($action['type'] == 'D') {
                            if ($relation) {
                                $RELATE_MODEL = $relation['model'];
                                $relate_records = $RELATE_MODEL::where($relation['foreign_key'], '=', $record->$relation['local_key'])->get();

                                foreach ($relate_records as $relate_record) {
                                    // 로깅모드이면 로그를 남김
                                    if ($logging_mode) {
                                        $relate_log = new CrushScheduleLog();
                                        $relate_log->userno = $list->userno;
                                        $relate_log->rule = serialize($options);
                                        $relate_log->term = $term;
                                        $relate_log->table_name = $relate_record->getTable();
                                        $relate_log->before_data = serialize($relate_record);
                                        $relate_log->after_data = 'DELETE_RELATE';
                                        $relate_log->save();
                                    }

                                    // 디버그 모드가 아닐때만 실제로 삭제함
                                    if (!$debug_mode) {
                                        $relate_record->delete();
                                    }
                                }
                            }

                            // 디버그 모드가 아닐때만 실제로 삭제함
                            if (!$debug_mode) {
                                $record->delete();
                            }

                            $log->after_data = 'DELETED';
                        } // 컬럼값 업데이트
                        else if ($action['type'] == 'U') {
                            foreach ($action['columns'] as $column => $init_value) {
                                $record->$column = $init_value;
                            }

                            // 디버그 모드가 아닐때만 실제로 컬럼값을 업데이트함
                            if (!$debug_mode) {
                                $record->save();
                            }

                            $log->after_data = serialize($record);
                        }

                        // 디버그모드이면 로그 데이터를 저장.
                        if ($logging_mode) {
                            $log->save();
                        }
                    }
                }
            }
        }
    }
}