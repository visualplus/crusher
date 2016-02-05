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
     * @param $member
     * @param $rule
     * @param $term
     * @param $foreignKey
     * @param $target
     * @return integer
     */
    private function crush($member, $rule, $term, $foreignKey, $target, $groupKey)
    {
        $debug_mode     = config('crusher.debug');
        $logging_mode   = config('crusher.log');

        $log = [
            'group_key'     => $groupKey,
            'userno'        => $member->idx,
            'rule'          => $rule,
            'term'          => $term,
            'table_name'    => $target->getTable(),
            'before_data'   => serialize($target),
        ];
        $affected = $target->privacyMasking($rule, $term, $foreignKey, $member);

        if ($affected == count($target->getAttributes())) {
            $log['after_data'] = 'DELETED';
        } else {
            $log['after_data'] = serialize($target);
        }

        if ($logging_mode && $affected > 0) {
            CrushScheduleLog::create($log);
        }

        if (!$debug_mode) {
            if ($affected == count($target->getAttributes())) {
                // 모든 컬럼이 업데이트됐으므로 해당 레코드를 삭제함.
                $target->delete();
            } else {
                // 일부 컬럼이 업데이트됐으므로 저장함
                $target->save();
            }
        }

        return $affected;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $terms          = config('crusher.terms');
        $models         = config('crusher.models');
        $groupKey       = Carbon::now()->format('Y-m-d H:i:s');

        // 즉시, 3개월, 5년 순차적으로 적용
        foreach ($terms as $term) {
            $lists = CrushSchedule::where('progressed_that', '<', $term)
                ->where('created_at', '<', Carbon::now()->subMonths($term)->format('Y-m-d 23:59:59'))
                ->with('member')->get();

            // 삭제 대상을 가져옴
            foreach ($lists as $list) {
                // 삭제할 데이터 모델들을 가져옴
                foreach ($models as $model) {
                    $_model = new $model['model'];
                    $indexKey = 'idx';
                    if (isset($model['key_column'])) {
                        $indexKey = $model['key_column'];
                    }
                    $targets = $_model->where($model['key'], '=', $list->member->$indexKey)->get();

                    // 삭제 대상 데이터 모델들의 컬럼들을 모두 업데이트함
                    foreach ($targets as $target) {
                        $this->crush($list->member, $list->rule, $term, $model['key'], $target, $groupKey);

                        // 연관 데이터도 삭제
                        if (isset($model['related'])) {
                            foreach ($model['related'] as $related) {
                                $_relatedModel = new $related['model'];
                                $relatedTargets = $_relatedModel->where($related['key'], '=', $related['parent'])->get();

                                foreach ($relatedTargets as $relatedTarget) {
                                    $this->crush($list->member, $list->rule, $term, $model['related']['key'], $relatedTarget);
                                }
                            }
                        }
                    }
                }

                $list->progressed_that = $term;
                $list->save();
            }
        }
    }
}